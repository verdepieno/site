<?php


namespace Webgriffe\QuiPago\Test\Integration;

use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\TestFramework\ObjectManager;
use Psr\Log\LoggerInterface;
use Webgriffe\LibQuiPago\Notification\Handler;
use Webgriffe\LibQuiPago\PaymentInit\UrlGenerator;

class DiConfigConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testQuiPagoUrlGeneratorHasLoggerInjected()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var ConfigInterface $diConfig */
        $diConfig = $objectManager->get(ConfigInterface::class);
        $arguments = $diConfig->getArguments(UrlGenerator::class);
        $this->assertContains('logger', array_keys($arguments));
        $this->assertEquals(LoggerInterface::class, $arguments['logger']['instance']);
    }

    public function testQuiPagoNotifyHandlerHasLoggerInjected()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var ConfigInterface $diConfig */
        $diConfig = $objectManager->get(ConfigInterface::class);
        $arguments = $diConfig->getArguments(Handler::class);
        $this->assertContains('logger', array_keys($arguments));
        $this->assertEquals(LoggerInterface::class, $arguments['logger']['instance']);
    }
}
