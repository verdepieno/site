<?php


namespace Webgriffe\QuiPago\Test\Integration;

use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\Base;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Request;
use Webgriffe\QuiPago\Controller\Redirect\Index;
use Webgriffe\QuiPago\Controller\Redirect\LastOrder;

class RouteConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @magentoAppArea frontend
     */
    public function testModuleRegistersQuiPagoFrontName()
    {
        /** @var ConfigInterface $routeConfig */
        $routeConfig = $this->objectManager->create(ConfigInterface::class);
        $this->assertContains('Webgriffe_QuiPago', $routeConfig->getModulesByFrontName('quipago'));
    }

    /**
     * @magentoAppArea frontend
     */
    public function testQuiPagoRedirectLastOrderActionCanBeFound()
    {
        /** @var Request $request */
        $request = $this->objectManager->create(Request::class);
        $request->setModuleName('quipago');
        $request->setControllerName('redirect');
        $request->setActionName('lastorder');

        /** @var Base $router */
        $router = $this->objectManager->create(Base::class);
        $this->assertInstanceOf(LastOrder::class, $router->match($request));
    }

    /**
     * @magentoAppArea frontend
     */
    public function testQuiPagoNotifyIndexActionCanBeFound()
    {
        /** @var Request $request */
        $request = $this->objectManager->create(Request::class);
        $request->setModuleName('quipago');
        $request->setControllerName('notify');
        $request->setActionName('index');

        /** @var Base $router */
        $router = $this->objectManager->create(Base::class);
        $this->assertInstanceOf('Webgriffe\QuiPago\Controller\Notify\Index', $router->match($request));
    }
}
