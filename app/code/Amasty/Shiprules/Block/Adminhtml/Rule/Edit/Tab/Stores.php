<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Block\Adminhtml\Rule\Edit\Tab;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;


class Stores extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Stores & Customer Groups');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Stores & Customer Groups');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_amasty_shiprules_rule');
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $hlp = $om->get('Amasty\Shiprules\Helper\Data');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fldStore = $form->addFieldset('apply_in', ['legend' => __('Apply In')]);

        $fldStore->addField('stores', 'multiselect', array(
            'label'     => __('Stores'),
            'name'      => 'stores[]',
            'values'    => $this->_systemStore->getStoreValuesForForm(),
            'note'      => __('Leave empty or select all to apply the rule to any'),
        ));

        $fldCust = $form->addFieldset('apply_for', array('legend'=> __('Apply For')));
        $fldCust->addField('cust_groups', 'multiselect', array(
            'name'      => 'cust_groups[]',
            'label'     => __('Customer Groups'),
            'values'    => $hlp->getAllGroups(),
            'note'      => __('Leave empty or select all to apply the rule to any group'),
        ));

        $form->setValues($model->getData());
        $form->addValues(['id'=>$model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}