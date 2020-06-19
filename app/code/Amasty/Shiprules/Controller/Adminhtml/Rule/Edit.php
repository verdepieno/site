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


class Edit extends \Amasty\Shiprules\Controller\Adminhtml\Rule
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Amasty\Shiprules\Model\Rule');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        } else {
            $this->_prepareForEdit($model);
        }
        $this->_coreRegistry->register('current_amasty_shiprules_rule', $model);
        $this->_initAction();
        if($model->getId()) {
            $title = __('Edit Shipping Rule `%1`', $model->getName());
        } else {
            $title = __("Add new Shipping Rule");
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->renderLayout();
    }

    public function _prepareForEdit(\Amasty\Shiprules\Model\Rule $model)
    {
        $fields = array('stores', 'cust_groups', 'carriers', 'days');
        foreach ($fields as $f){
            $val = $model->getData($f);
            if (!is_array($val)){
                $model->setData($f, explode(',', $val));
            }
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        return true;
    }
}