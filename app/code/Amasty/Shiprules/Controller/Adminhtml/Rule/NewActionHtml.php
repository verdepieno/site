<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Controller\Adminhtml\Rule;


class NewActionHtml extends \Amasty\Shiprules\Controller\Adminhtml\Rule
{
    public function execute()
    {
        $this->newConditions('actions');
    }
}