<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Model;

class Rule extends \Magento\Rule\Model\AbstractModel
{
    const CALC_REPLACE = 0;
    const CALC_ADD     = 1;
    const CALC_DEDUCT  = 2;

    const ALL_ORDERS = 0;
    const BACKORDERS_ONLY = 1;
    const NON_BACKORDERS = 2;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;




    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {

        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        parent::__construct(
            $context, $registry, $formFactory, $localeDate, null, null, $data
        );

    }

    public function validate(\Magento\Framework\DataObject $object)
    {
        return $this->getConditions()->validateNotModel($object);
    }


    protected function _construct()
    {
        $this->_init('Amasty\Shiprules\Model\ResourceModel\Rule');
        parent::_construct();
    }


    public function getConditionsInstance()
    {
        return $this->objectManager->create('Amasty\Shiprules\Model\Rule\Condition\Combine');
    }

    public function getActionsInstance()
    {
        return $this->objectManager->create('Amasty\Shiprules\Model\Rule\Condition\Product\Combine');
    }

    public function massChangeStatus($ids, $status)
    {
        return $this->getResource()->massChangeStatus($ids, $status);
    }

    /**
     * Initialize rule model data from array
     *
     * @param   array $rule
     * @return  Mage_SalesRule_Model_Rule
     */
    public function loadPost(array $rule)
    {
        $arr = $this->_convertFlatToRecursive($rule);
        if (isset($arr['conditions'])) {
            $this->getConditions()->setConditions(array())->loadArray($arr['conditions'][1]);
        }
        if (isset($arr['actions'])) {
            $this->getActions()->setActions(array())->loadArray($arr['actions'][1], 'actions');
        }
        return $this;
    }

    public function match($rate)
    {
        if (false === strpos($this->getCarriers(), ',' . $rate->getCarrier(). ',')){
            return false;
        }

        $m = $this->getMethods();
        $m = str_replace("\r\n", "\n", $m);
        $m = str_replace("\r", "\n", $m);
        $m = trim($m);
        if (!$m){ // any method
            return true;
        }

        $m = array_unique(explode("\n", $m));
        foreach ($m as $pattern){
            $pattern = '/' . trim($pattern) . '/i';
            if (preg_match($pattern, $rate->getMethodTitle())){
                return true;
            }
        }
        return false;
    }

    public function validateTotals($totals)
    {
        $keys = array('price', 'qty', 'weight');
        foreach ($keys as $k){
            $v = $this->getIgnorePromo() ? $totals[$k] : $totals['not_free_' . $k];
            if ($this->getData($k . '_from') > 0 && $v < $this->getData($k . '_from')){
                return false;
            }

            if ($this->getData($k . '_to')   > 0 && $v > $this->getData($k . '_to')){
                return false;
            }
        }

        return true;
    }

    //chnages inner variable fee
    public function calculateFee($totals, $isFree)
    {
        if ($isFree && !$this->getIgnorePromo()){
            $this->setFee(0);
            return 0;
        }

        $rate = 0;

        // fixed per each item
        $qty = $this->getIgnorePromo() ? $totals['qty'] : $totals['not_free_qty'];
        $weight = $this->getIgnorePromo() ? $totals['weight'] : $totals['not_free_weight'];
        if ($qty > 0){
            // base rate, but only in cases at lest one product is not free
            $rate += $this->getRateBase();
        }

        $rate += $qty * $this->getRateFixed();

        // percent per each item
        $price = $this->getIgnorePromo() ? $totals['price'] : $totals['not_free_price'];
        $rate += $price * $this->getRatePercent() / 100;
        $rate += $weight * $this->getWeightFixed();

        if ($this->getCalc() == self::CALC_DEDUCT){
            $rate = 0 - $rate; // negative
        }

        $this->setFee($rate);

        return $rate;
    }

    public function removeFromRequest()
    {
        return ($this->getCalc() == self::CALC_REPLACE);
    }

    public function afterSave()
    {
        //Saving attributes used in rule
        $ruleProductAttributes = array_merge(
            $this->_getUsedAttributes($this->getConditionsSerialized()),
            $this->_getUsedAttributes($this->getActionsSerialized())
        );
        if (count($ruleProductAttributes)) {
            $this->getResource()->saveAttributes($this->getId(), $ruleProductAttributes);
        }

        return parent::afterSave();
    }

    /**
     * Return all product attributes used on serialized action or condition
     *
     * @param string $serializedString
     * @return array
     */
    protected function _getUsedAttributes($serializedString)
    {
        $result = array();
        $pattern = '~s:46:"Magento\\\SalesRule\\\Model\\\Rule\\\Condition\\\Product";s:9:"attribute";s:\d+:"(.*?)"~s';
        $matches = array();
        if (preg_match_all($pattern, $serializedString, $matches)){
            foreach ($matches[1] as $attributeCode) {
                $result[] = $attributeCode;
            }
        }

        return $result;
    }


    protected function _setWebsiteIds()
    {
        $websites = array();

        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $websites[$website->getId()] = $website->getId();
                }
            }
        }

        $this->setOrigData('website_ids', $websites);
    }



    public function beforeSave()
    {
        $this->_setWebsiteIds();
        return parent::beforeSave();
    }

    public function beforeDelete()
    {
        $this->_setWebsiteIds();
        return parent::beforeDelete();
    }

}
