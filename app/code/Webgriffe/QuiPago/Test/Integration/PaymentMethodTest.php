<?php


namespace Webgriffe\QuiPago\Test\Integration;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Logger\Monolog;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory;
use Magento\TestFramework\ObjectManager;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Webgriffe\QuiPago\Model\PaymentMethod;

class PaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $baseLogger;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->baseLogger = $this->prophesize(LoggerInterface::class);
        $this->objectManager->addSharedInstance($this->baseLogger->reveal(), Monolog::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store payment/quipago/active 1
     */
    public function testPaymentMethodIsRegistered()
    {
        /** @var MethodList $methodsList */
        $methodsList = $this->objectManager->create('\Magento\Payment\Model\MethodList');
        /** @var Quote $quote */
        $quote = $this->objectManager->create('\Magento\Quote\Api\Data\CartInterface');
        $quote->setStoreId(1);

        $availableMethods = $methodsList->getAvailableMethods($quote);
        $this->assertInternalType('array', $availableMethods);
        $this->assertQuipagoInMethodsList($availableMethods);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base BSD
     * @magentoConfigFixture current_store currency/options/default BSD
     * @magentoConfigFixture current_store currency/options/allow USD,BSD
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/quote.php
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The base currency "BSD" it's not supported by payment method "QuiPago Payment Gateway".
     */
    public function testValidateThrowsAnExceptionIfBaseCurrencyIsNotSupported()
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $this->assertNotNull($quote->getId());
        $quote->setBaseCurrencyCode('BSD'); // BSD => Bahamian Dollar (not supported currency)
        $quote->getPayment()->setMethod('quipago');
        $quote->save();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $quote->getPayment()->getMethodInstance();
        $paymentMethod->validate();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testValidateDoesNotThrowAnExceptionIfBaseCurrencyIsSupported()
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $this->assertNotNull($quote->getId());
        $quote->setBaseCurrencyCode('EUR'); // Euro (supported currency)
        $quote->getPayment()->setMethod('quipago');
        $quote->save();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $quote->getPayment()->getMethodInstance();
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod->validate());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testInitializePaymentMethodSetsProperOrderPlaceRedirectUrlAndPaymentAction()
    {
        $order = $this->setupOrderWithQuiPagoPaymentMethod('100000001', 'EUR');
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();

        $stateObject = $this->objectManager->create(DataObject::class);
        $paymentMethod->initialize(AbstractMethod::ACTION_AUTHORIZE_CAPTURE, $stateObject);
        $this->assertPendingPaymentStateObjectAndNotNotified($stateObject);
        $this->assertFalse($order->getCanSendNewEmailFlag());
        $this->assertEquals(100, $order->getPayment()->getAmountAuthorized());
        $this->assertEquals(100, $order->getPayment()->getBaseAmountAuthorized());
        
        $orderPlaceRedirectUrl = $paymentMethod->getOrderPlaceRedirectUrl();
        $this->assertNotEmpty($orderPlaceRedirectUrl);
        $this->assertEquals('https', parse_url($orderPlaceRedirectUrl, PHP_URL_SCHEME));
        $this->assertEquals('ecommerce.keyclient.it', parse_url($orderPlaceRedirectUrl, PHP_URL_HOST));
        $this->assertEquals('/ecomm/ecomm/DispatcherServlet', parse_url($orderPlaceRedirectUrl, PHP_URL_PATH));
        $queryParams = $this->getQueryParamsFromUrl($orderPlaceRedirectUrl);
        $this->assertEquals('merchant_alias', $queryParams['alias']);
        $this->assertEquals('10000', $queryParams['importo']);
        $this->assertEquals('EUR', $queryParams['divisa']);
        $this->assertEquals('100000001', $queryParams['codTrans']);
        $this->assertEquals('http://localhost/index.php/checkout/onepage/failure/', $queryParams['url_back']);
        $this->assertEquals('customer@null.com', $queryParams['mail']);
        $this->assertEquals('http://localhost/index.php/quipago/redirect/result/', $queryParams['url']);
        $this->assertEquals('ORDER-100000001', $queryParams['session_id']);
        $this->assertEquals('GER', $queryParams['languageId']);
        $this->assertEquals('http://localhost/index.php/quipago/notify/index/', $queryParams['urlpost']);
        $this->assertRegExp('/[A-z0-9]{40}/', $queryParams['mac']);
        $this->assertEquals(
            AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
            $order->getPayment()->getAdditionalInformation(PaymentMethod::PAYMENT_ACTION_ADDITIONAL_INFO_KEY)
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base USD
     * @magentoConfigFixture current_store currency/options/default USD
     * @magentoConfigFixture current_store currency/options/allow USD
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 1
     * @magentoConfigFixture current_store payment/quipago/language ITA
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testInitializePaymentMethodInTestModeUsesAmountOfOneAndTestData()
    {
        $order = $this->setupOrderWithQuiPagoPaymentMethod('100000001', 'EUR');
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();

        $stateObject = $this->objectManager->create(DataObject::class);
        $paymentMethod->initialize(AbstractMethod::ACTION_AUTHORIZE_CAPTURE, $stateObject);
        $this->assertPendingPaymentStateObjectAndNotNotified($stateObject);
        $this->assertFalse($order->getCanSendNewEmailFlag());
        $this->assertEquals(100, $order->getPayment()->getAmountAuthorized());
        $this->assertEquals(100, $order->getPayment()->getBaseAmountAuthorized());

        $orderPlaceRedirectUrl = $paymentMethod->getOrderPlaceRedirectUrl();
        $this->assertNotEmpty($orderPlaceRedirectUrl);
        $this->assertEquals('https', parse_url($orderPlaceRedirectUrl, PHP_URL_SCHEME));
        $this->assertEquals('coll-ecommerce.keyclient.it', parse_url($orderPlaceRedirectUrl, PHP_URL_HOST));
        $this->assertEquals('/ecomm/ecomm/DispatcherServlet', parse_url($orderPlaceRedirectUrl, PHP_URL_PATH));
        $queryParams = $this->getQueryParamsFromUrl($orderPlaceRedirectUrl);
        $this->assertEquals('payment_3444153', $queryParams['alias']);
        $this->assertEquals('0100', $queryParams['importo']);
        $this->assertEquals('EUR', $queryParams['divisa']);
        $this->assertRegExp('/[A-z0-9]{13}-100000001/', $queryParams['codTrans']);
        $this->assertEquals('http://localhost/index.php/checkout/onepage/failure/', $queryParams['url_back']);
        $this->assertEquals('customer@null.com', $queryParams['mail']);
        $this->assertEquals('http://localhost/index.php/quipago/redirect/result/', $queryParams['url']);
        $this->assertRegExp('/ORDER-[A-z0-9]{13}-100000001/', $queryParams['session_id']);
        $this->assertEquals('ITA', $queryParams['languageId']);
        $this->assertEquals('http://localhost/index.php/quipago/notify/index/', $queryParams['urlpost']);
        $this->assertRegExp('/[A-z0-9]{40}/', $queryParams['mac']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandlePositiveNotifyRegistersCaptureNotification()
    {
        $orderIncrementId = '100000001';
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE_CAPTURE);
        
        $this->assertNull($this->getOrder($orderIncrementId)->getBaseTotalPaid());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
            ]
        );
        
        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertTrue($return);
        $this->assertRegisteredCaptureNotificationForOrder($this->getOrder($orderIncrementId), $orderIncrementId);
        $this->baseLogger->debug(sprintf('Currently handling notify for order "#%s"...', $orderIncrementId))
            ->shouldBeCalled();
        $this->baseLogger->debug('Transaction result is positive')->shouldBeCalled();
        $this->baseLogger->debug(sprintf('Payment action is "%s"', AbstractMethod::ACTION_AUTHORIZE_CAPTURE))
            ->shouldBeCalled();
        $this->baseLogger->debug(sprintf('Registered capture notification for order "%s"', $orderIncrementId))
            ->shouldBeCalled();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 1
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandlePositiveNotifyInTestModeWhichUsesAmountOfOneEuro()
    {
        $orderIncrementId = '100000001';
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE_CAPTURE);

        $this->assertNull($this->getOrder($orderIncrementId)->getBaseTotalPaid());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $transactionCode = sprintf('%s-%s', '1234567890123', $orderIncrementId);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '100',
                'divisa' => 'EUR',
                'codTrans' => $transactionCode,
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => 'c5217f1d675babe23d58da52ce93aa64ce28a46b'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertTrue($return);
        $this->assertRegisteredCaptureNotificationForOrder($this->getOrder($orderIncrementId), $transactionCode);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Webgriffe/QuiPago/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandleNegativeNotifyDoesNotRegistersCaptureNotification()
    {
        $orderIncrementId = '100000001';
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE_CAPTURE);

        $order = $this->getOrder($orderIncrementId);
        $this->assertNull($order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'KO',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => 'b6a868eea875a0e5a17453bdd35b2f2dfaff664f'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertFalse($return);
        $order = $this->getOrder($orderIncrementId);
        $this->assertNull($order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
        $this->baseLogger->debug(sprintf('Currently handling notify for order "#%s"...', $orderIncrementId))
            ->shouldBeCalled();
        $this->baseLogger->debug('Transaction result is negative')->shouldBeCalled();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 1
     * @magentoDataFixture Webgriffe/QuiPago/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandleNegativeNotifyCancelOrderIfConfigured()
    {
        $orderIncrementId = '100000001';
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE_CAPTURE);

        $order = $this->getOrder($orderIncrementId);
        $this->assertNull($order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'KO',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => 'b6a868eea875a0e5a17453bdd35b2f2dfaff664f'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertFalse($return);
        $order = $this->getOrder($orderIncrementId);
        $this->assertNull($order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
        $this->assertEquals($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED), $order->getStatus());
        $this->baseLogger->debug(sprintf('Currently handling notify for order "#%s"...', $orderIncrementId))
            ->shouldBeCalled();
        $this->baseLogger->debug('Transaction result is negative')->shouldBeCalled();
        $this->baseLogger->debug(
            sprintf(
                'Payment method is configured to cancel failed orders and order "%s" can be canceled...',
                $orderIncrementId
            )
        )
            ->shouldBeCalled();
        $this->baseLogger->debug(sprintf('Order "#%s" has been canceled', $orderIncrementId))->shouldBeCalled();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot handle notify. Order with increment ID "90000001" is not found.
     */
    public function testHandleNotifyThrowsAnExceptionIfOrderDoesNotExists()
    {
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => '90000001',
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '2a9a7c2f3b12c6b4282a185c09c6b2333d386e6a'
            ]
        );
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->objectManager->get(PaymentMethod::class);
        $paymentMethod->handleNotify($request);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot handle notify. Order "100000001" is not paid with QuiPago (method is "checkmo").
     */
    public function testHandleNotifyThrowsAnExceptionIfOrderIsNotPaidWithQuiPago()
    {
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => '100000001',
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
            ]
        );
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->objectManager->get(PaymentMethod::class);
        $paymentMethod->handleNotify($request);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandlePositiveAuthNotifyRegistersAuthorizationNotification()
    {
        $orderIncrementId = '100000001';
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE);

        $this->assertNull($this->getOrder($orderIncrementId)->getBaseTotalPaid());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertTrue($return);
        $this->assertRegisteredAuthorizationForOrder($this->getOrder($orderIncrementId));
        $this->baseLogger->debug(sprintf('Currently handling notify for order "#%s"...', $orderIncrementId))
            ->shouldBeCalled();
        $this->baseLogger->debug('Transaction result is positive')->shouldBeCalled();
        $this->baseLogger->debug(sprintf('Payment action is "%s"', AbstractMethod::ACTION_AUTHORIZE))
            ->shouldBeCalled();
        $this->baseLogger->debug(sprintf('Registered authorization notification for order "%s"', $orderIncrementId))
            ->shouldBeCalled();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandlePositiveAuthNotifyUsesPaymentActionUsedDuringPaymentInitialization()
    {
        $orderIncrementId = '100000001';
        // Note that the configured payment action is "authorize_capture" but on payment we have "authorize" that should
        // be used as payment action.
        $this->prepareOrderForNotificationHandling($orderIncrementId, AbstractMethod::ACTION_AUTHORIZE);

        $this->assertNull($this->getOrder($orderIncrementId)->getBaseTotalPaid());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertTrue($return);
        $this->assertRegisteredAuthorizationForOrder($this->getOrder($orderIncrementId));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoAdminConfigFixture currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store currency/options/allow USD,EUR
     * @magentoConfigFixture current_store payment/quipago/active 1
     * @magentoConfigFixture current_store payment/quipago/test 0
     * @magentoConfigFixture current_store payment/quipago/production_merchant_alias merchant_alias
     * @magentoConfigFixture current_store payment/quipago/production_mac_key secret_key
     * @magentoConfigFixture current_store payment/quipago/language GER
     * @magentoConfigFixture current_store payment/quipago/hashing_method sha1
     * @magentoConfigFixture current_store payment/quipago/payment_action authorize
     * @magentoConfigFixture current_store payment/quipago/cancel_on_failure 0
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     * @magentoDataFixture Webgriffe/QuiPago/_files/currency_rates.php
     */
    public function testHandlePositiveAuthNotifyUsesPaymentActionFromConfigIfNotOnPayment()
    {
        $orderIncrementId = '100000001';
        // Note that the configured payment action is "authorize_capture" but on payment we have "authorize" that should
        // be used as payment action.
        $this->prepareOrderForNotificationHandling($orderIncrementId, null);

        $this->assertNull($this->getOrder($orderIncrementId)->getBaseTotalPaid());
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(
            [
                'alias' => 'merchant_alias',
                'importo' => '10000',
                'divisa' => 'EUR',
                'codTrans' => $orderIncrementId,
                'esito' => 'OK',
                'codAut' => 'TESTOK',
                'data' => '20110616',
                'orario' => '174003',
                'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
            ]
        );

        $order = $this->getOrder($orderIncrementId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $return = $paymentMethod->handleNotify($request);

        $this->assertTrue($return);
        $this->assertRegisteredAuthorizationForOrder($this->getOrder($orderIncrementId));
    }

    private function assertQuipagoInMethodsList($methods)
    {
        $quipagoCode = 'quipago';
        /** @var PaymentMethodInterface $method */
        foreach ($methods as $method) {
            if ($method->getCode() === $quipagoCode) {
                return;
            }
        }
        $this->fail(sprintf('Payment method "%s" is not in payment methods list.', $quipagoCode));
    }

    /**
     * @param $orderIncrementId
     * @param $baseCurrencyCode
     * @return Order
     */
    private function setupOrderWithQuiPagoPaymentMethod($orderIncrementId, $baseCurrencyCode)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create('Magento\Sales\Model\Order');
        $order->load($orderIncrementId, 'increment_id');
        $order->setBaseCurrencyCode($baseCurrencyCode);
        $order->setGrandTotal($order->getBaseGrandTotal());

        $payment = $order->getPayment();
        $payment->setMethod(PaymentMethod::METHOD_CODE);
        $order->save();

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->objectManager->create('\Webgriffe\QuiPago\Model\PaymentMethod');
        $paymentMethod->setInfoInstance($payment);

        return $order;
    }

    /**
     * @param $orderPlaceRedirectUrl
     * @return array
     */
    private function getQueryParamsFromUrl($orderPlaceRedirectUrl)
    {
        $queryParams = [];
        parse_str(parse_url($orderPlaceRedirectUrl, PHP_URL_QUERY), $queryParams);
        return $queryParams;
    }

    /**
     * @param $stateObject
     */
    private function assertPendingPaymentStateObjectAndNotNotified($stateObject)
    {
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $stateObject->getState());
        $this->assertEquals('pending_payment', $stateObject->getStatus());
        $this->assertFalse($stateObject->getIsNotified());
    }

    /**
     * @param $orderIncrementId
     * @return Order
     */
    private function getOrder($orderIncrementId)
    {
        return $this->objectManager->create(Order::class)->loadByIncrementId($orderIncrementId);
    }

    /**
     * @param $order
     * @param $transactionCode
     */
    private function assertRegisteredCaptureNotificationForOrder($order, $transactionCode)
    {
        $this->assertEquals(100, $order->getTotalPaid());
        $this->assertEquals(100, $order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertEquals($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING), $order->getStatus());
        $this->assertTrue((bool)$order->getEmailSent());

        $invoices = $order->getInvoiceCollection();
        $this->assertCount(1, $invoices);
        /** @var Invoice $invoice */
        $invoice = $invoices->getFirstItem();
        $this->assertEquals(100, $invoice->getBaseGrandTotal());
        $this->assertEquals(Invoice::STATE_PAID, $invoice->getState());

        /** @var History[] $comments */
        $comments = array_values($order->getStatusHistoryCollection()->getItems());
        $this->assertCount(2, $comments);
        $this->assertContains(
            sprintf('You notified customer about invoice #%1.', $invoice->getIncrementId()),
            $comments[0]->getComment()
        );
        $this->assertContains(
            sprintf(
                'QuiPago successful payment result message received (codTrans="%s", codAut="%s").',
                $transactionCode,
                'TESTOK'
            ),
            $comments[1]->getComment()
        );

        /** @var Collection $orderTransactions */
        $orderTransactions = $this->objectManager->create(CollectionFactory::class)->create()
            ->addOrderIdFilter($order->getId());
        $this->assertCount(1, $orderTransactions);
        /** @var Transaction $transaction */
        $transaction = $orderTransactions->getFirstItem();
        $this->assertFalse((bool)$transaction->getIsClosed());
        $this->assertEquals(Transaction::TYPE_CAPTURE, $transaction->getTxnType());
        $this->assertRegExp('/[0-9A-Za-z]{13}/', $transaction->getTxnId());
    }

    /**
     * @param $orderIncrementId
     * @param $paymentAction
     * @return Order
     */
    private function prepareOrderForNotificationHandling($orderIncrementId, $paymentAction)
    {
        $order = $this->getOrder($orderIncrementId);
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $payment->setMethod(PaymentMethod::METHOD_CODE);
        $payment->setAdditionalInformation(PaymentMethod::PAYMENT_ACTION_ADDITIONAL_INFO_KEY, $paymentAction);
        $order->setBaseCurrencyCode('EUR');
        $order->setGrandTotal($order->getBaseGrandTotal());
        $order->setTotalDue($order->getGrandTotal());
        $order->setBaseTotalDue($order->getBaseGrandTotal());
        $order->save();
        return $order;
    }

    /**
     * @param $order
     */
    private function assertRegisteredAuthorizationForOrder($order)
    {
        $this->assertNull($order->getTotalPaid());
        $this->assertNull($order->getBaseTotalPaid());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertEquals($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING), $order->getStatus());
        $this->assertTrue((bool)$order->getEmailSent());

        $invoices = $order->getInvoiceCollection();
        $this->assertCount(0, $invoices);

        /** @var History[] $comments */
        $comments = array_values($order->getStatusHistoryCollection()->getItems());
        $this->assertCount(1, $comments);
        $this->assertContains(
            sprintf(
                'QuiPago successful payment result message received (codTrans="%s", codAut="%s").',
                $order->getIncrementId(),
                'TESTOK'
            ),
            $comments[0]->getComment()
        );

        /** @var Collection $orderTransactions */
        $orderTransactions = $this->objectManager->create(CollectionFactory::class)->create()
            ->addOrderIdFilter($order->getId());
        $this->assertCount(1, $orderTransactions);
        /** @var Transaction $transaction */
        $transaction = $orderTransactions->getFirstItem();
        $this->assertFalse((bool)$transaction->getIsClosed());
        $this->assertEquals(Transaction::TYPE_AUTH, $transaction->getTxnType());
        $this->assertRegExp('/[0-9A-Za-z]{13}/', $transaction->getTxnId());
    }
}
