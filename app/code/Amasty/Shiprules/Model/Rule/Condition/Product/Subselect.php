<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model\Rule\Condition\Product;

class Subselect extends \Magento\SalesRule\Model\Rule\Condition\Product\Subselect
{
    /**
     * Subselect constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context          $context
     * @param \Amasty\Shiprules\Model\Rule\Condition\Product $ruleConditionProduct
     * @param array                                          $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Amasty\Shiprules\Model\Rule\Condition\Product $ruleConditionProduct,
        array $data = []
    ) {
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->setType('Amasty\Shiprules\Model\Rule\Condition\Product\Subselect')
            ->setValue(null);
    }

    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();
        foreach($conditions as $key=>$value) {
            if(is_array($value)) {
                if(isset($value['label']) && $value['label'] == __('Product Attribute')) {
                    $conditions[$key]['value'][] =
                        [
                            'label' => __('Backorders'),
                            'value' => 'Amasty\Shiprules\Model\Rule\Condition\Product|is_backorder'

                        ];
                }
                if(isset($value['value']) && $value['value'] == 'Magento\SalesRule\Model\Rule\Condition\Product\Combine') {
                    $conditions[$key]['value'] = 'Amasty\Shiprules\Model\Rule\Condition\Product\Combine';
                }
            }
        }
        return $conditions;
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'qty'                       => __('total quantity'),
            'base_row_total'            => __('total amount excl. tax'),
            'base_row_total_incl_tax'   => __('total amount incl. tax'),
            'row_weight'                => __('total weight'),
        ));
        return $this;
    }

    /**
     * validate
     *
     * @param Varien_Object $object Quote
     * @return boolean
     */
    public function validate(\Magento\Framework\Model\AbstractModel $object)
    {
        return $this->validateNotModel($object);
    }

    public function validateNotModel($object)
    {
        $attr = $this->getAttribute();
        $total = 0;
        if ($object->getAllItems()) {
            $validIds = array();
            foreach ($object->getAllItems() as $item) {


                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $item->getProduct()->setTypeId('skip');
                }

                //can't use parent here
                if (\Magento\SalesRule\Model\Rule\Condition\Product\Combine::validate(
                    $item
                )
                ) {
                    $itemParentId = $item->getParentItemId();
                    if (is_null($itemParentId)) {
                        $validIds[] = $item->getItemId();
                    } else {
                        if (in_array($itemParentId, $validIds)) {
                            continue;
                        } else {
                            $validIds[] = $itemParentId;
                        }
                    }


                    $total += $item->getData($attr);
                }

                if ($item->getProduct()->getTypeId() === 'skip') {
                    $item->getProduct()->setTypeId('configurable');
                }
            }
        }

        return $this->validateAttribute($total);
    }
}
