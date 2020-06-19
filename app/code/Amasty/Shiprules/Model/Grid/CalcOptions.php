<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Model\Grid;

class CalcOptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Amasty\Shiprules\Helper\Data
     */
    protected $_helper;

    /**
     * @param \Amasty\Shiprules\Helper\Data $helper
     */
    public function __construct(\Amasty\Shiprules\Helper\Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Return backup types array
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_helper->getCalculations();
    }
}