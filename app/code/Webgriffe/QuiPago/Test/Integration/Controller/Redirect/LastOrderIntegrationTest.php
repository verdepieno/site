<?php


namespace Webgriffe\QuiPago\Test\Integration\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Webgriffe\QuiPago\Model\PaymentMethod;

class LastOrderIntegrationTest extends AbstractController
{
    const QUIPAGO_PAYMENT_URL = 'http://quipago.payment.url/';
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        parent::setUp();
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoDataFixture Magento/Sales/_files/order_pending_payment.php
     */
    public function testRedirectsToQuiPagoPaymentUrl()
    {
        $this->setupOrder('100000001');
        $this->dispatch('quipago/redirect/lastorder');
        $this->assertSame(302, $this->getResponse()->getHttpResponseCode());
        $this->assertContains(self::QUIPAGO_PAYMENT_URL, $this->getResponse()->getHeader('Location')->toString());
    }

    private function setupOrder($incrementId)
    {
        /** @var Order $order */
        $order = $this->objectManager->create(Order::class);
        $order->loadByIncrementId($incrementId);
        $order->getPayment()->setAdditionalInformation(
            PaymentMethod::PAYMENT_URL_ADDITIONAL_INFO_KEY,
            self::QUIPAGO_PAYMENT_URL
        );
        $order->save();
        
        $checkoutSession = $this->objectManager->get(Session::class);
        $checkoutSession->setLastRealOrderId($incrementId);
    }
}
