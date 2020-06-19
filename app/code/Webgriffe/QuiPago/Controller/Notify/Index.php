<?php


namespace Webgriffe\QuiPago\Controller\Notify;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Webgriffe\QuiPago\Model\PaymentMethod;

class Index extends Action
{
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
        PaymentMethod $paymentMethod,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->paymentMethod = $paymentMethod;
        $this->logger = $logger;
    }

    /**
     * Dispatch request
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->debug(sprintf('%s method called', __METHOD__));
        $this->logger->debug(sprintf('Request params are %s', json_encode($this->getRequest()->getParams())));
        try {
            if ($this->paymentMethod->handleNotify($this->getRequest())) {
                $this->logger->debug('Successful notification handled.');
                return $this->getResponse();
            }
            $this->logger->debug('Failed notification handled.');
            return $this->getResponse();
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('There has been an error while handling notify request: %s', $e->getMessage())
            );
            throw $e;
        }
    }
}
