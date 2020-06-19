<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model\Rule\Condition;


use Magento\Rule\Model\Condition\Context;

class Product extends \Magento\SalesRule\Model\Rule\Condition\Product
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $configYesNo;

    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        array $data = []
    ) {
        $this->configYesNo = $yesno;
        parent::__construct(
            $context, $backendData, $config, $productFactory,
            $productRepository, $productResource, $attrSetCollection,
            $localeFormat, $data
        );
    }

    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['is_backorder'] = __('Backorders');
    }

    protected function _prepareValueOptions()
    {
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');
        if ($selectReady && $hashedReady) {
            return $this;
        }
        if ($this->getAttribute() === 'is_backorder') {
            $this->_setSelectOptions($this->configYesNo->toOptionArray(), $selectReady, $hashedReady);
        }
        parent::_prepareValueOptions();
    }


    public function getInputType()
    {
        if ($this->getAttribute() === 'is_backorder') {
            return 'is_backorder';
        }

        return parent::getInputType();
    }

    public function getValueElementType()
    {
        if ($this->getAttribute() === 'is_backorder') {
            return 'select';
        }
        return parent::getValueElementType();
    }

    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['is_backorder'] = ['=='];
        }
        return $this->_defaultOperatorInputByType;
    }

    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        if ($this->getAttribute() === 'is_backorder') {
            if($model->getBackorders() > 0) {
                $isBackorder = 1;
            } else {
                $isBackorder = 0;
            }
            return $this->validateAttribute($isBackorder);
        }
        return parent::validate($model);
    }


}
