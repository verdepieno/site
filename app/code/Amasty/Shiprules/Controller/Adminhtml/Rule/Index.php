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

class Index extends \Amasty\Shiprules\Controller\Adminhtml\Rule
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Amasty_Shiprules::rule');
        $resultPage->getConfig()->getTitle()->prepend(__('Shipping Rules'));
        $resultPage->addBreadcrumb(__('Shipping Rules'), __('Shipping Rules'));
        return $resultPage;
    }
}