<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Plugin;

class Shipping
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->objectManager = $objectManager;
    }

    public function aroundCollectRates(
        \Magento\Shipping\Model\Shipping $subject,
        \Closure $closure,
        \Magento\Quote\Model\Quote\Address\RateRequest $request
    )
    {
        $closure($request);

        $result   = $subject->getResult();

        $oldRates = $result->getAllRates();
        $oldPrices = $this->_getPrices($oldRates);
        $newRates = array();

        $validator = $this->objectManager->get('Amasty\Shiprules\Model\Validator');
        $validator->init($request);
        if (!$validator->canApplyFor($oldRates)){
            return $subject;
        }

        $validator->applyRulesTo($oldRates);
        foreach ($oldRates as $rate){
            if ($validator->needNewRequest($rate)){

                $newRequest = $validator->getNewRequest($rate);
                if (count($newRequest->getAllItems())){

                    $result->reset();
                    $closure($newRequest);

                    $rate = $validator->findRate($result->getAllRates(), $rate);
                }
                else {
                    $rate->setPrice(0);
                }
            }
            $rate->setPrice($rate->getPrice() + $validator->getFee($rate));
            $newRates[] = $rate;
        }

        $result->reset();
        foreach ($newRates as $rate) {
            $rate->setOldPrice($oldPrices[$rate->getMethod()]);
            $rate->setPrice(max(0, $rate->getPrice()));
            $result->append($rate);
        }
        return $subject;
    }

    protected function _getPrices($rates)
    {
        $prices = array();
        foreach ($rates as $rate) {
            $prices[$rate->getMethod()] = $rate->getPrice();
        }
        return $prices;
    }
}
