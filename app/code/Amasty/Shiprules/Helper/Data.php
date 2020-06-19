<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_counter;
    protected $_firstTime = true;


    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    public function __construct(Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry
    )
    {
        $this->objectManager = $objectManager;
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    public function getAllGroups()
    {
        $customerGroups = $this->objectManager->create('Magento\Customer\Model\ResourceModel\Group\Collection')
            ->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value']==0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift($customerGroups, array('value'=>0, 'label'=>__('NOT LOGGED IN')));
        }

        return $customerGroups;
    }

    public function getAllCarriers()
    {
        $carriers = [];
        foreach ($this->scopeConfig->getValue('carriers') as $code=>$config){
            if (!empty($config['title'])){
                $carriers[] = ['value'=>$code, 'label'=>$config['title'] . ' [' . $code . ']'];
            }
        }
        return $carriers;
    }

    public function getStatuses()
    {
        return array(
            '1' => __('Active'),
            '0' => __('Inactive'),
        );
    }

    public function getCalculations()
    {
        $a = array(
            \Amasty\Shiprules\Model\Rule::CALC_REPLACE  => __('Replace'),
            \Amasty\Shiprules\Model\Rule::CALC_ADD      => __('Surcharge'),
            \Amasty\Shiprules\Model\Rule::CALC_DEDUCT   => __('Discount'),
        );
        return $a;
    }

    public function getAllDays()
    {
        return array(
            array('value'=>'7', 'label' => __('Sunday')),
            array('value'=>'1', 'label' => __('Monday')),
            array('value'=>'2', 'label' => __('Tuesday')),
            array('value'=>'3', 'label' => __('Wednesday')),
            array('value'=>'4', 'label' => __('Thursday')),
            array('value'=>'5', 'label' => __('Friday')),
            array('value'=>'6', 'label' => __('Saturday')),
        );
    }

    public function getAllRules()
    {
        $rules =  array(
            array('value'=>'0', 'label' => ' '));

        $rulesCollection = $this->objectManager->create('Magento\SalesRule\Model\ResourceModel\Rule\Collection');

        foreach ($rulesCollection as $rule){
            $rules[] = array('value'=>$rule->getRuleId(), 'label' => $rule->getName());
        }

        return $rules;
    }

}