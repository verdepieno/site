<?php


namespace Webgriffe\QuiPago\Test\Integration;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\ObjectManager;

class ModuleConfigTest extends \PHPUnit_Framework_TestCase
{
    private $moduleName = 'Webgriffe_QuiPago';

    public function testModuleIsRegistered()
    {
        $registrar = ObjectManager::getInstance()->get(ComponentRegistrar::class);
        $this->assertArrayHasKey($this->moduleName, $registrar->getPaths(ComponentRegistrar::MODULE));
    }

    public function testModuleIsConfiguredAndEnabled()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();
        /** @var ModuleList $moduleList */
        $moduleList = $objectManager->create(ModuleList::class);
        $this->assertTrue($moduleList->has($this->moduleName));
    }
}
