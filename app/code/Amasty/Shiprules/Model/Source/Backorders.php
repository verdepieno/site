<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Model\Source;
use Amasty\Shiprules\Model\Rule;


class Backorders
{
    public function toArray()
    {
        return [
            Rule::ALL_ORDERS => __('All orders'),
            Rule::BACKORDERS_ONLY => __('Backorders only'),
            Rule::NON_BACKORDERS => __('Non backorders')
        ];
    }
}
