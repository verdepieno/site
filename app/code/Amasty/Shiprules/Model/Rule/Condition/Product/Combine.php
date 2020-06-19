<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model\Rule\Condition\Product;


class Combine extends \Magento\SalesRule\Model\Rule\Condition\Product\Combine
{
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Amasty\Shiprules\Model\Rule\Condition\Product $ruleConditionProduct,
        array $data = []
    ) {
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->setType('Amasty\Shiprules\Model\Rule\Condition\Product\Combine');
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
}
