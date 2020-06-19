<?php


namespace Webgriffe\QuiPago\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class PaymentMethod extends AbstractMethod
{
    const METHOD_CODE = 'quipago';

    // @codingStandardsIgnoreStart
    // Ignoring MEQP2.PHP.ProtectedClassMember.FoundProtected which comes from core class
    protected $_code = self::METHOD_CODE;
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    // @codingStandardsIgnoreEnd
    const SESSION_ID_PREFIX = 'ORDER-';
    const LANGUAGE_ID_ITA = 'ITA';
    const TEST_MODE_CURRENCY_CODE = 'EUR';
    const PAYMENT_URL_ADDITIONAL_INFO_KEY = 'quipago_payment_url';
    const PAYMENT_ACTION_ADDITIONAL_INFO_KEY = 'quipago_payment_action';

    /**
     * @var \Webgriffe\QuiPago\PaymentInit\UrlGenerator
     */
    private $quipagoUrlGenerator;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Webgriffe\QuiPago\Notification\Handler
     */
    private $notificationHandler;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $baseLogger;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Webgriffe\QuiPago\PaymentInit\UrlGenerator $urlGenerator,
        \Webgriffe\QuiPago\Notification\Handler $notificatonHandler,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $baseLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        // TODO QuiPago UrlGenerator non viene instanziato con logger quando Magento è in "default" mode
        $this->quipagoUrlGenerator = $urlGenerator;
        $this->urlBuilder = $urlBuilder;
        $this->notificationHandler = $notificatonHandler;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->baseLogger = $baseLogger;
    }

    public function validate()
    {
        parent::validate();
        
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof OrderPayment) {
            $baseCurrencyCode = $paymentInfo->getOrder()->getBaseCurrencyCode();
        } else {
            $baseCurrencyCode = $paymentInfo->getQuote()->getBaseCurrencyCode();
        }
        
        if (!in_array($baseCurrencyCode, array_keys($this->quipagoUrlGenerator->getAllowedCurrencies()))) {
            throw new \RuntimeException(
                sprintf(
                    'The base currency "%s" it\'s not supported by payment method "%s".',
                    $baseCurrencyCode,
                    $this->getTitle()
                )
            );
        }
        
        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case AbstractMethod::ACTION_AUTHORIZE:
            case AbstractMethod::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                /** @var Order $order */
                $order = $payment->getOrder();
                $order->setCanSendNewEmailFlag(false);
                $payment->setAmountAuthorized($order->getTotalDue());
                $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
                $this->setQuiPagoPaymentUrl($payment);
                $payment->setAdditionalInformation(self::PAYMENT_ACTION_ADDITIONAL_INFO_KEY, $paymentAction);
                $stateObject->setState(Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT));
                $stateObject->setIsNotified(false);
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Payment method "%s" does not support "%s" payment action.',
                        $this->getCode(),
                        $paymentAction
                    )
                );
                break;
        }
        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->getQuiPagoPaymentUrl($this->getInfoInstance());
    }

    public function handleNotify(RequestInterface $request)
    {
        $this->notificationHandler->handle(
            $this->getMacKey(),
            $this->getConfigData('hashing_method'),
            $request->getParams()
        );

        $order = $this->getOrder();
        $this->baseLogger->debug(sprintf('Currently handling notify for order "#%s"...', $order->getIncrementId()));

        if (!$this->notificationHandler->isTransactionResultPositive()) {
            $this->baseLogger->debug('Transaction result is negative');
            if ($this->getConfigData('cancel_on_failure') && $order->canCancel()) {
                $this->baseLogger->debug(
                    sprintf(
                        'Payment method is configured to cancel failed orders and order "%s" can be canceled...',
                        $order->getIncrementId()
                    )
                );
                $order->cancel()->save();
                $this->baseLogger->debug(sprintf('Order "#%s" has been canceled', $order->getIncrementId()));
            }
            return false;
        }

        $this->baseLogger->debug('Transaction result is positive');
        
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();
        $this->importInfoToPayment($payment);

        $this->baseLogger->debug(sprintf('Payment action is "%s"', $this->getPaymentAction($payment)));

        if ($this->isAuthorizeCapturePaymentAction($payment)) {
            $this->handleNotificationAsCapture($payment);
            $this->baseLogger->debug(
                sprintf('Registered capture notification for order "%s"', $order->getIncrementId())
            );
        } elseif ($this->isOnlyAuthorizePaymentAction($payment)) {
            $this->handleNotificationAsAuthorization($payment);
            $this->baseLogger->debug(
                sprintf(
                    'Registered authorization notification for order "%s"',
                    $order->getIncrementId()
                )
            );
        } else {
            throw new \RuntimeException(
                sprintf(
                    'Invalid payment action "%s" for payment method "%s". Allowed payment actions are %s.',
                    $this->getPaymentAction($payment),
                    $this->getCode(),
                    implode(', ', [AbstractMethod::ACTION_AUTHORIZE_CAPTURE, AbstractMethod::ACTION_AUTHORIZE])
                )
            );
        }

        return true;
    }

    /**
     * @return mixed
     */
    private function getGatewayUrl()
    {
        if ($this->isTestMode()) {
            return $this->getConfigData('test_gateway_url');
        }
        return $this->getConfigData('production_gateway_url');
    }

    private function isTestMode()
    {
        return (bool)$this->getConfigData('test');
    }

    /**
     * @return mixed
     */
    private function getMerchantAlias()
    {
        if ($this->isTestMode()) {
            return $this->getConfigData('test_merchant_alias');
        }
        return $this->getConfigData('production_merchant_alias');
    }

    /**
     * @return mixed
     */
    public function getMacKey()
    {
        if ($this->isTestMode()) {
            return $this->getConfigData('test_mac_key');
        }
        return $this->getConfigData('production_mac_key');
    }

    /**
     * @param Order $order
     * @return float
     */
    private function getAmount(Order $order)
    {
        if ($this->isTestMode()) {
            return 1;
        }
        return $order->getPayment()->getBaseAmountAuthorized();
    }

    /**
     * @return string
     */
    private function getCurrencyCode(Order $order)
    {
        if ($this->isTestMode()) {
            return self::TEST_MODE_CURRENCY_CODE;
        }
        return $order->getBaseCurrencyCode();
    }

    /**
     * @param InfoInterface $payment
     */
    private function setQuiPagoPaymentUrl(InfoInterface $payment)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $transactionCode = $this->getTransactionCodeFromIncrementId($order->getIncrementId());
        $url = $this->quipagoUrlGenerator->generate(
            $this->getGatewayUrl(),
            $this->getMerchantAlias(),
            $this->getMacKey(),
            $this->getConfigData('hashing_method'),
            $this->getAmount($order),
            $this->getCurrencyCode($order),
            $transactionCode,
            $this->urlBuilder->getUrl('checkout/onepage/failure'),
            $order->getCustomerEmail() ?: $order->getBillingAddress()->getEmail(),
            $this->urlBuilder->getUrl('quipago/redirect/result'),
            sprintf('%s%s', self::SESSION_ID_PREFIX, $transactionCode),
            $this->getConfigData('language'),
            $this->urlBuilder->getUrl('quipago/notify/index')
        );
        $payment->setAdditionalInformation(self::PAYMENT_URL_ADDITIONAL_INFO_KEY, $url);
    }

    /**
     * @param InfoInterface $payment
     * @return string
     */
    private function getQuiPagoPaymentUrl(InfoInterface $payment)
    {
        return $payment->getAdditionalInformation(self::PAYMENT_URL_ADDITIONAL_INFO_KEY);
    }

    /**
     * @param OrderPayment $payment
     */
    private function importInfoToPayment(OrderPayment $payment)
    {
        $payment->setTransactionId(strtoupper(uniqid()));
        $payment->setCurrencyCode($this->notificationHandler->getCurrency());
        $payment->setPreparedMessage(
            sprintf(
                'QuiPago successful payment result message received (codTrans="%s", codAut="%s").',
                $this->notificationHandler->getTransactionCode(),
                $this->notificationHandler->getAuthCode()
            )
        );
        $payment->setIsTransactionClosed(0);
    }

    /**
     * @param $orderIncrementId
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrder($orderIncrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * @param Order $order
     * @return float
     */
    private function getNotificationAmount(Order $order)
    {
        if ($this->isTestMode()) {
            return $order->getBaseTotalDue();
        }
        return $this->notificationHandler->getAmount();
    }

    /**
     * @return Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrder()
    {
        $orderId = $this->getIncrementIdFromTransactionCode();
        $order = $this->loadOrder($orderId);

        if (!$order->getId()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot handle notify. Order with increment ID "%s" is not found.', $orderId)
            );
        }
        if ($order->getPayment()->getMethod() !== $this->getCode()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot handle notify. Order "%s" is not paid with QuiPago (method is "%s").',
                    $orderId,
                    $order->getPayment()->getMethod()
                )
            );
        }
        return $order;
    }

    /**
     * @param $payment
     */
    private function handleNotificationAsCapture($payment)
    {
        $order = $payment->getOrder();
        $payment->registerCaptureNotification($this->getNotificationAmount($order));
        $order->save();
        /** @var Invoice $invoice */
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order
                ->addStatusHistoryComment(
                    __('You notified customer about invoice #%1.', $invoice->getIncrementId())
                )
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    /**
     * @param $payment
     */
    private function handleNotificationAsAuthorization($payment)
    {
        $order = $payment->getOrder();
        $payment->registerAuthorizationNotification($this->getNotificationAmount($order));
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }
        $order->save();
    }

    /**
     * @param $payment
     * @return bool
     */
    private function isAuthorizeCapturePaymentAction($payment)
    {
        return $this->getPaymentAction($payment) === AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * @param $payment
     * @return bool
     */
    private function isOnlyAuthorizePaymentAction($payment)
    {
        return $this->getPaymentAction($payment) === AbstractMethod::ACTION_AUTHORIZE;
    }

    /**
     * @param $payment
     * @return mixed
     */
    private function getPaymentAction($payment)
    {
        $infoPaymentAction = $payment->getAdditionalInformation(self::PAYMENT_ACTION_ADDITIONAL_INFO_KEY);
        return $infoPaymentAction ?: $this->getConfigPaymentAction();
    }

    /**
     * @param string $incrementId
     * @return string
     */
    private function getTransactionCodeFromIncrementId($incrementId)
    {
        if ($this->isTestMode()) {
            $incrementId = sprintf('%s-%s', strtoupper(uniqid()), $incrementId);
        }
        return $incrementId;
    }

    /**
     * @return string
     */
    private function getIncrementIdFromTransactionCode()
    {
        $transactionCode = $this->notificationHandler->getTransactionCode();
        if ($this->isTestMode()) {
            $transactionCode = substr($transactionCode, strlen(uniqid()) + 1);
        }
        return $transactionCode;
    }
}
