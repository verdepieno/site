<?php


namespace Webgriffe\QuiPago\Test\Integration\Controller\Redirect;

use Magento\Framework\Logger\Monolog;
use Magento\TestFramework\TestCase\AbstractController;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ResultIntegrationTest extends AbstractController
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    protected function setUp()
    {
        parent::setUp();
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->_objectManager->addSharedInstance($this->logger->reveal(), Monolog::class);
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
    public function testRedirectsToSuccessWithPositiveResultRequest()
    {
        $request = $this->getRequest();
        $params = [
            'alias' => 'merchant_alias',
            'importo' => '10000',
            'divisa' => 'EUR',
            'codTrans' => '100000001',
            'esito' => 'OK',
            'codAut' => 'TESTOK',
            'data' => '20110616',
            'orario' => '174003',
            'mac' => '5f0241262c3789f841be7056aa7539a57d188a86'
        ];
        $request->setParams($params);

        $this->dispatch('quipago/redirect/result');
        $this->assertSame(302, $this->getResponse()->getHttpResponseCode());
        $this->assertContains('checkout/onepage/success', $this->getResponse()->getHeader('Location')->toString());
        $this->logger->debug('Webgriffe\QuiPago\Controller\Redirect\Result::execute method called')->shouldBeCalled();
        $this->logger->debug(sprintf('Request params are: %s', json_encode($params)))->shouldBeCalled();
        $this->logger->debug(
            Argument::allOf(
                Argument::containingString('Redirecting to'),
                Argument::containingString('checkout/onepage/success')
            )
        )->shouldBeCalled();
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
    public function testRedirectsToFailureWithNegativeResultRequest()
    {
        $request = $this->getRequest();
        $params = [
            'alias' => 'merchant_alias',
            'importo' => '10000',
            'divisa' => 'EUR',
            'codTrans' => '100000001',
            'esito' => 'KO',
            'data' => '20110616',
            'orario' => '174003',
            'mac' => 'b6a868eea875a0e5a17453bdd35b2f2dfaff664f'
        ];
        $request->setParams($params);
        $this->dispatch('quipago/redirect/result');
        $this->assertSame(302, $this->getResponse()->getHttpResponseCode());
        $this->assertContains('checkout/onepage/failure', $this->getResponse()->getHeader('Location')->toString());
        $this->logger->debug('Webgriffe\QuiPago\Controller\Redirect\Result::execute method called')->shouldBeCalled();
        $this->logger->debug(sprintf('Request params are: %s', json_encode($params)))->shouldBeCalled();
        $this->logger->debug(
            Argument::allOf(
                Argument::containingString('Redirecting to'),
                Argument::containingString('checkout/onepage/failure')
            )
        )->shouldBeCalled();
    }
}
