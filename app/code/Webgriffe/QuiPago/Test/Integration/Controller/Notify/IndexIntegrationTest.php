<?php


namespace Webgriffe\QuiPago\Test\Integration\Controller\Notify;

use Magento\Framework\Logger\Monolog;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Webgriffe\QuiPago\Model\PaymentMethod;

class IndexIntegrationTest extends AbstractController
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;
    /**
     * @var PaymentMethod|ObjectProphecy
     */
    private $paymentMethod;

    protected function setUp()
    {
        parent::setUp();
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->paymentMethod = $this->prophesize(PaymentMethod::class);
        $this->_objectManager->addSharedInstance($this->logger->reveal(), Monolog::class);
        $this->_objectManager->addSharedInstance($this->paymentMethod->reveal(), PaymentMethod::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testPositiveNotifyReturns200Response()
    {
        $this->paymentMethod->handleNotify($this->getRequest())->shouldBeCalled()->willReturn(true);

        $this->dispatch('quipago/notify/index');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testNegativeNotifyReturns200Response()
    {
        $this->paymentMethod->handleNotify($this->getRequest())->shouldBeCalled()->willReturn(false);

        $this->dispatch('quipago/notify/index');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testLogsRequestParamsAndSuccessfulResult()
    {
        $request = $this->getRequest();
        $request->setParam('param1', 'value1');
        $request->setParam('param2', 'value2');
        $this->_objectManager->addSharedInstance($this->paymentMethod->reveal(), PaymentMethod::class);
        $this->paymentMethod->handleNotify($request)->willReturn(true);

        $this->logger->debug('Webgriffe\QuiPago\Controller\Notify\Index::execute method called')->shouldBeCalled();
        $this->logger->debug('Request params are {"param1":"value1","param2":"value2"}')->shouldBeCalled();
        $this->logger->debug('Successful notification handled.')->shouldBeCalled();

        $this->dispatch('quipago/notify/index');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     */
    public function testLogsFailedResult()
    {
        $this->_objectManager->addSharedInstance($this->paymentMethod->reveal(), PaymentMethod::class);
        $this->paymentMethod->handleNotify($this->getRequest())->willReturn(false);

        $this->logger->debug('Webgriffe\QuiPago\Controller\Notify\Index::execute method called')->shouldBeCalled();
        $this->logger->debug('Request params are []')->shouldBeCalled();
        $this->logger->debug('Failed notification handled.')->shouldBeCalled();

        $this->dispatch('quipago/notify/index');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Exception message
     */
    public function testLogsErrors()
    {
        $this->_objectManager->addSharedInstance($this->paymentMethod->reveal(), PaymentMethod::class);
        $this->paymentMethod->handleNotify($this->getRequest())->willThrow(new \Exception('Exception message'));

        $this->logger->debug('Webgriffe\QuiPago\Controller\Notify\Index::execute method called')->shouldBeCalled();
        $this->logger->debug('Request params are []')->shouldBeCalled();
        $this->logger->error('There has been an error while handling notify request: Exception message')
            ->shouldBeCalled();

        $this->dispatch('quipago/notify/index');
    }
}
