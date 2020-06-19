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


class ProductAttributes
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

    public function aroundGetProductAttributes(\Magento\Quote\Model\Quote\Config $subject, \Closure $closure)
    {
        $attributesTransfer = $closure();

        $attributes = $this->objectManager->create('Amasty\Shiprules\Model\ResourceModel\Rule')->getAttributes();

        //$result = array();
        foreach ($attributes as $code) {
            $attributesTransfer[] = $code;
            //$result[$code] = true;
        }
        //$attributesTransfer->addData($result);

        return $attributesTransfer;

    }
}
