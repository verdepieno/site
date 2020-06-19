<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model\Rule\Condition;


class Address extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_directoryCountry;

    /**
     * @var \Magento\Directory\Model\Config\Source\Allregion
     */
    protected $_directoryAllregion;

    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Directory\Model\Config\Source\Country $directoryCountry,
        \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_directoryCountry = $directoryCountry;
        $this->_directoryAllregion = $directoryAllregion;
    }

    public function loadAttributeOptions()
    {
        $attributes = array(
            'package_value'    => __('Subtotal'),
            'package_value_with_discount'   => __('Subtotal with discount'),
            'package_qty'      => __('Total Items Quantity'),
            'package_weight'   => __('Total Weight'),
            'dest_postcode'    => __('Shipping Postcode'),
            'dest_region_id'   => __('Shipping State/Province'),
            'dest_country_id'  => __('Shipping Country'),
            'dest_city'        => __('Shipping City'),
            'dest_street'      => __('Shipping Address Line'),
        );

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'package_value': case 'package_weight': case 'package_qty':
            return 'numeric';

            case 'dest_country_id': case 'dest_region_id':
            return 'select';
        }
        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'dest_country_id': case 'dest_region_id':
            return 'select';
        }
        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'dest_country_id':
                    $options = $this->_directoryCountry->toOptionArray();
                    break;

                case 'dest_region_id':
                    $options = $this->_directoryAllregion->toOptionArray();
                    break;

                default:
                    $options = array();
            }
            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

    public function getOperatorSelectOptions()
    {
        $operators = $this->getOperatorOption();
        if ($this->getAttribute() == 'dest_street') {
            $operators = array(
                '{}'  => __('contains'),
                '!{}' => __('does not contain'),
                '{%'  => __('starts from'),
                '%}'  => __('ends with'),
            );
        }

        $type = $this->getInputType();
        $opt = array();
        $operatorByType = $this->getOperatorByInputType();
        foreach ($operators as $k => $v) {
            if (!$operatorByType || in_array($k, $operatorByType[$type])) {
                $opt[] = array('value' => $k, 'label' => $v);
            }
        }
        return $opt;
    }

    public function getDefaultOperatorInputByType()
    {
        $op = parent::getDefaultOperatorInputByType();
        $op['string'][] = '{%';
        $op['string'][] = '%}';
        return $op;
    }

    public function getDefaultOperatorOptions()
    {
        $op = parent::getDefaultOperatorOptions();
        $op['{%'] = __('starts from');
        $op['%}'] = __('ends with');

        return $op;
    }

    public function validateAttribute($validatedValue)
    {
        if (is_object($validatedValue)) {
            return false;
        }

        if (is_string($validatedValue)){
            $validatedValue = strtoupper($validatedValue);
        }

        /**
         * Condition attribute value
         */
        $value = $this->getValueParsed();
        if (is_string($value)){
            $value = strtoupper($value);
        }

        /**
         * Comparison operator
         */
        $op = $this->getOperatorForValidate();

        // if operator requires array and it is not, or on opposite, return false
        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        $result = false;
        switch ($op) {
            case '{%':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = substr($validatedValue,0,strlen($value)) == $value;
                }
                break;
            case '%}':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = substr($validatedValue,-strlen($value)) == $value;
                }
                break;
            default:
                return parent::validateAttribute($validatedValue);
                break;
        }
        return $result;
    }
}