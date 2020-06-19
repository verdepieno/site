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


class Rates extends Generic implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Rates');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Rates');
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

        $fldRate = $form->addFieldset('rate', array('legend'=> __('Rates')));
        $fldRate->addField('calc', 'select', array(
            'label'     => __('Calculation'),
            'name'      => 'calc',
            'options'   => $hlp->getCalculations(),
        ));
        $fldRate->addField('rate_base', 'text', array(
            'label'     => __('Base Rate for the Order'),
            'name'      => 'rate_base',
        ));
        $fldRate->addField('rate_fixed', 'text', array(
            'label'     => __('Fixed Rate per Product'),
            'name'      => 'rate_fixed',
        ));

        $fldRate->addField('weight_fixed', 'text', array(
            'label'     => __('Rate per unit of weight'),
            'name'      => 'weight_fixed',
            'note'      => __("Enter the surcharge or discount amount that'll be automatically multiplied by the product's weight to create a shipping rate."),
        ));

        $fldRate->addField('rate_percent', 'text', array(
            'label'     => __('Percentage per Product'),
            'name'      => 'rate_percent',
            'note'      => __('Percentage of original product cart price is taken, without discounts.'),
        ));

        $fldRate->addField('handling', 'text', array(
            'label'     => __('Handling Percentage'),
            'name'      => 'handling',
            'note'      => __('The percentage will be added or deducted from the shipping rate. If it is 10% and UPS Ground is $25, the total shipping cost will be $27.5'),
        ));

        $fldRate->addField('rate_min', 'text', array(
            'label'     => __('Minimal rate change'),
            'name'      => 'rate_min',
            'note'      => __('This is the minimal amount, which will be added or deducted by this rule.'),
        ));

        $fldRate->addField('rate_max', 'text', array(
            'label'     => __('Maximal rate change'),
            'name'      => 'rate_max',
            'note'      => __('This is the maximum amount, which will be added or deducted by this rule.'),
        ));

        $fldRate->addField('ship_min', 'text', array(
            'label'     => __('Minimal rate'),
            'name'      => 'ship_min',
            'note'      => __('Minimal total rate after the rule is applied.'),
        ));

        $fldRate->addField('ship_max', 'text', array(
            'label'     => __('Maximal total rate'),
            'name'      => 'ship_max',
            'note'      => __('Maximal total rate after the rule is applied.'),
        ));



        $form->setValues($model->getData());
        $form->addValues(['id'=>$model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}