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
use Magento\Quote\Model\Quote\Item as QI;
class Validator extends \Magento\Framework\DataObject
{
    protected $adjustments = array();

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    )
    {
        $this->objectManager = $objectManager;
        parent::__construct($data);
    }

    public function init($request)
    {
        $this->setRequest($request);
        return $this;
    }

    public function applyRulesTo($rates)
    {
        $request = $this->getRequest();

        $affectedIds = array();

        foreach ($rates as $rate){
            $this->adjustments[$this->getKey($rate)] = array(
                'fee'    => 0,
                'totals' => $this->initTotals(),
                'ids'    => array(),
            );
            $affectedIds[$this->getKey($rate)] = array();
        }

        foreach ($this->getValidRules() as $rule){
            $rule->setFee(0);

            $group = array();
            foreach ($request->getAllItems() as $item){
                if (!($rule->getActions()->validate($item))){
                    continue;
                }
                $group[$item->getItemId()] = $item;
            }

            if (!$group){
                continue;
            }

            $subTotals = $this->aggregateTotals($group, $request->getFreeShipping());
            if ($rule->validateTotals($subTotals)){

                $rule->calculateFee($subTotals, $request->getFreeShipping());

                foreach ($rates as $rate){

                    // already affeced products
                    $currentIds = array_keys($group);
                    $oldIds     = $affectedIds[$this->getKey($rate)];
                    if ($rule->match($rate) && !count(array_intersect($currentIds, $oldIds))){

                        $affectedIds[$this->getKey($rate)] = array_merge($currentIds, $oldIds);

                        $a = $this->adjustments[$this->getKey($rate)];
                        $a['fee'] += $rule->getFee();


                        $handling = $rule->getHandling(); // new field
                        if (is_numeric($handling)){
                            if($rule->getCalc() == \Amasty\Shiprules\Model\Rule::CALC_DEDUCT){
                                $a['fee'] -= $rate->getPrice() * $handling /100;
                            } else {
                                $a['fee'] += $rate->getPrice() * $handling /100;
                            }
                        }

                        if ($rule->removeFromRequest()){
                            // remember removed group totals
                            foreach ($subTotals as $k=>$value){
                                if (isset($a['totals'][$k])){
                                    $a['totals'][$k] += $value;
                                }
                            }
                            // remember removed group ids
                            $a['ids'] = array_merge($a['ids'], array_keys($group));
                        }//if remove

                        if($rule->getRateMax() > 0){
                            $a['fee'] = ($a['fee'] > 0 ? 1: -1) * min(abs($a['fee']),$rule->getRateMax());
                        }

                        if($rule->getRateMin() > 0){
                            if($rule->getCalc() == \Amasty\Shiprules\Model\Rule::CALC_DEDUCT){
                                //add min rate change negative for discount action
                                $a['fee'] = ($a['fee'] <= 0 ? -1: 1) * max(abs($a['fee']),$rule->getRateMin());
                            } else {
                                //add min rate change positive for other actions
                                $a['fee'] = ($a['fee'] >= 0 ? 1: -1) * max(abs($a['fee']),$rule->getRateMin());
                            }
                        }
                        if ($rule->getShipMin() > 0){
                            if ($rate->getCost() + $a['fee'] < $rule->getShipMin()){
                                $a['fee'] = $rule->getShipMin() - $rate->getCost();
                            }
                        }

                        if ($rule->getShipMax() > 0){
                            if ($rate->getCost() + $a['fee'] > $rule->getShipMax()){
                                $a['fee'] = $rule->getShipMax()  - $rate->getCost();
                            }
                        }

                        $this->adjustments[$this->getKey($rate)] = $a;

                    }
                }
            }// if group totals valid
        }// foreach rule

        //$newRequest = $this->getModifiedRequest($request, $idsToRemove, $totalsToDeduct);
        return $this;
    }

    public function needNewRequest($rate)
    {
        $k = $this->getKey($rate);
        if (empty($this->adjustments[$k]))
            return false;

        return (count($this->adjustments[$k]['ids']));
    }

    public function getNewRequest($rate)
    {
        $a = $this->adjustments[$this->getKey($rate)];

        $totalsToDeduct = $a['totals'];
        $idsToRemove    = $a['ids'];

        $newRequest = clone $this->getRequest();

        $newItems = array();
        foreach ($newRequest->getAllItems() as $item){
            $id = $item->getItemId();
            if (in_array($id, $idsToRemove)){
                continue;
            }
            $newItems[] = $item;
        }
        $newRequest->setAllItems($newItems);

        $newRequest->setPackageValue($newRequest->getPackageValue() - $totalsToDeduct['price']);
        $newRequest->setPackageWeight($newRequest->getPackageWeight() - $totalsToDeduct['weight']);
        $newRequest->setPackageQty($newRequest->getPackageQty() - $totalsToDeduct['qty']);
        $newRequest->setFreeMethodWeight($newRequest->getFreeMethodWeight() - $totalsToDeduct['not_free_weight']);

        //@todo - calculate discount?
        $newRequest->setPackageValueWithDiscount($newRequest->getPackageValue());
        $newRequest->setPackagePhysicalValue($newRequest->getPackageValue());

        return $newRequest;
    }

    public function canApplyFor($rates)
    {
        //@todo check for free shipping

        $request = $this->getRequest();

        if (!count($request->getAllItems()))
            return false;

        $firstItem = current($request->getAllItems());
        if ($firstItem->getQuote()->isVirtual()){
            return false;
        }

        // minimal check
        $rules = $this->getAllRules();
        foreach ($rules as $rule){
            foreach ($rates as $rate){
                if ($rule->match($rate)){
                    return true;
                }
            }
        }

        return false;
    }

    public function getValidRules()
    {
        $request = $this->getRequest();

        $hash = $this->getAddressHash($request);
        if ($this->getData('rules_by_'. $hash)){
            return $this->getData('rules_by_'. $hash);
        }

        $validRules = array();
        foreach ($this->getAllRules() as $rule){
            /**
             * @var $rule \Amasty\Shiprules\Model\Rule
             */
            $rule->afterLoad();
            if ($this->isCouponValid($request, $rule) && $rule->validate($request)){
                $validRules[] = $rule;
            }
        }

        $this->setData('rule_by_'. $hash, $validRules);

        return $validRules;
    }

    public function isCouponValid($request, $rule)
    {
        $actualCouponCode  = trim(strtolower($rule->getCoupon()));
        $actualDiscountId  = intVal($rule->getDiscountId());

        if (!$actualCouponCode && !$actualDiscountId)
            return true;

        $providedCouponCodes = $this->getCouponCodes($request);

        if ($actualCouponCode){
            return (in_array($actualCouponCode, $providedCouponCodes));
        }

        if ($actualDiscountId){
            foreach ($providedCouponCodes as $code){
                $couponModel         = $this->objectManager->create('Magento\SalesRule\Model\Coupon')->load($code, 'code');
                $providedDiscountId  = $couponModel->getRuleId();

                if ($providedDiscountId == $actualDiscountId){
                    return true;
                }
                $couponModel = null;
            }

        }

        return false;
    }

    public function getCouponCodes($request)
    {
        if (!count($request->getAllItems()))
            return array();

        $firstItem = current($request->getAllItems());
        $codes = trim(strtolower($firstItem->getQuote()->getCouponCode()));

        if (!$codes)
            return array();

        $providedCouponCodes = explode(",",$codes);

        foreach ($providedCouponCodes as $key => $code){
            $providedCouponCodes[$key] = trim($code);
        }

        return $providedCouponCodes;

    }


    public function getAllRules()
    {
        if (!$this->getData('rules_all')){
            $request = $this->getRequest();

            $hasBackOrders = false;
            foreach ($request->getAllItems() as $item){
                if ($item->getBackorders() > 0 ){
                    $hasBackOrders = true;
                    break;
                }
            }
            $backOrdersCondition = array(Rule::ALL_ORDERS);
            if($hasBackOrders) {
                $backOrdersCondition[] = Rule::BACKORDERS_ONLY;
            } else {
                $backOrdersCondition[] = Rule::NON_BACKORDERS;
            }
            $collection = $this->objectManager->create('Amasty\Shiprules\Model\Rule')
                ->getCollection()
                ->addFieldToFilter('is_active', 1)
                ->addStoreFilter($request->getStoreId())
                ->addCustomerGroupFilter($this->getCustomerGroupId())
                ->addDaysFilter()
                ->addFieldToFilter('out_of_stock', array('in' => $backOrdersCondition))
                ->setOrder('pos','asc')
                ->load();
            $this->setData('rules_all', $collection);
        }

        return $this->getData('rules_all');
    }

    public function getCustomerGroupId()
    {
        $request = $this->getRequest();
        $groupId = 0;

        $firstItem = current($request->getAllItems());
        if ($firstItem->getQuote()->getCustomerId()){
            $groupId = $firstItem->getQuote()->getCustomer()->getGroupId();
        }

        return $groupId;
    }

    public function getAddressHash($request)
    {
        $addressCondition = $this->objectManager->create('Amasty\Shiprules\Model\Rule\Condition\Address');//Mage::getModel('amshiprules/rule_condition_address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();

        $hash = '';
        foreach ($addressAttributes as $code => $label) {
            $hash .= $request->getData($code) . $label;
        }

        return md5($hash);
    }

    public function aggregateTotals($group, $isFree)
    {
        $totals = $this->initTotals();

        foreach ($group as $item) { /** @var QI $item */

            if ($item->getParentItem() || $item->getProduct()->isVirtual()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) { /** @var QI $child */
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }

                    $qty        = $item->getQty() * $child->getQty();
                    $notFreeQty = $item->getQty() * ($qty - $this->getFreeQty($child));

                    $totals['qty']          += $qty;
                    $totals['not_free_qty'] += $notFreeQty;

                    // 2018-03-22 Dmitry Fedyuk https://mage2.pro/u/dmitry_fedyuk
                    $totals['price']          += $child->getBaseRowTotalInclTax();
                    $totals['not_free_price'] += $child->getBasePriceInclTax() * $notFreeQty;

                    if (!$item->getProduct()->getWeightType()) {
                        $totals['weight']          += $child->getWeight() * $qty;
                        $totals['not_free_weight'] += $child->getWeight() * $notFreeQty;
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $totals['weight']          += $item->getWeight() * $item->getQty();
                    $totals['not_free_weight'] += $item->getWeight() * ($item->getQty() - $this->getFreeQty($item));
                }
            }
            else { // normal product

                $qty        = $item->getQty();
                $notFreeQty = ($qty - $this->getFreeQty($item));

                $totals['qty']          += $qty;
                $totals['not_free_qty'] += $notFreeQty;

                // 2018-03-22 Dmitry Fedyuk https://mage2.pro/u/dmitry_fedyuk
                $totals['price']          += $item->getBaseRowTotalInclTax();
                $totals['not_free_price'] += $item->getBasePriceInclTax() * $notFreeQty;

                $totals['weight']          += $item->getWeight() * $qty;
                $totals['not_free_weight'] += $item->getWeight() * $notFreeQty;

            } // if normal products
        }// foreach

        if ($isFree){
            $totals['not_free_price'] = $totals['not_free_weight'] = $totals['not_free_qty'] = 0;
        }

        return $totals;
    }

    public function getFreeQty($item)
    {
        $freeQty = 0;
        if ($item->getFreeShipping()){
            $freeQty = (is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : $item->getQty());
        }
        return $freeQty;
    }

    public function initTotals()
    {
        $totals = array(
            'price'              => 0,
            'not_free_price'     => 0,
            'weight'             => 0,
            'not_free_weight'    => 0,
            'qty'                => 0,
            'not_free_qty'       => 0,
        );
        return $totals;
    }

    public function getKey($rate)
    {
        return $rate->getCarrier() . '~' . $rate->getMethod();
    }

    public function findRate($newRates, $rate)
    {
        foreach ($newRates as $r){
            if ($this->getKey($r) == $this->getKey($rate)){
                return $r;
            }
        }
        // @todo return error?
        return $rate;
    }

    public function getFee($rate)
    {
        $k = $this->getKey($rate);
        if (empty($this->adjustments[$k]))
            return 0;

        return $this->adjustments[$k]['fee'];
    }
}
