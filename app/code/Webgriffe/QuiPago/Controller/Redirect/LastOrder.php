<?php


namespace Webgriffe\QuiPago\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\OrderFactory;
use Webgriffe\QuiPago\Model\PaymentMethod;

class LastOrder extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    public function __construct(Context $context, Session $checkoutSession, OrderFactory $orderFactory)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->getResponse()->setRedirect(
            $this->getOrder()->getPayment()->getAdditionalInformation(PaymentMethod::PAYMENT_URL_ADDITIONAL_INFO_KEY)
        );
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->orderFactory->create()->loadByIncrementId($this->checkoutSession->getLastRealOrderId());
    }
}
