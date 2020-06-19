<?php


namespace Webgriffe\QuiPago\Controller\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Webgriffe\QuiPago\Notification\Handler;
use Webgriffe\QuiPago\Model\PaymentMethod;

class Result extends Action
{
    /**
     * @var Handler
     */
    private $notificationHandler;
    /**
     * @var PaymentMethod
     */
    private $paymentMethod;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        Handler $notificationHandler,
        PaymentMethod $paymentMethod,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->notificationHandler = $notificationHandler;
        $this->paymentMethod = $paymentMethod;
        $this->logger = $logger;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->logger->debug(sprintf('%s method called', __METHOD__));
        $this->logger->debug(sprintf('Request params are: %s', json_encode($params)));
        $this->notificationHandler->handle(
            $this->paymentMethod->getMacKey(),
            $this->paymentMethod->getConfigData('hashing_method'),
            $params
        );
        $url = $this->_url->getUrl('checkout/onepage/failure');
        if ($this->notificationHandler->isTransactionResultPositive()) {
            $url = $this->_url->getUrl('checkout/onepage/success');
        }
        $this->logger->debug(sprintf('Redirecting to %s', $url));
        return $this->getResponse()->setRedirect($url);
    }
}
