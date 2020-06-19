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


class General extends Generic implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('General');
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

        $fieldset = $form->addFieldset('general', ['legend' => __('General')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Name'), 'title' => __('Name'), 'required' => true]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label'     => __('Status'),
                'title'     => __('Status'),
                'name'      => 'is_active',
                'options'    => $hlp->getStatuses(),
            ]
        );
        $fieldset->addField('carriers', 'multiselect', array(
            'label'     => __('Shipping Carriers'),
            'title'     => __('Shipping Carriers'),
            'name'      => 'carriers[]',
            'values'    => $hlp->getAllCarriers(),
        ));

        $fieldset->addField('methods', 'textarea', array(
            'label'     => __('Shipping Methods'),
            'title'     => __('Shipping Methods'),
            'name'      => 'methods',
            'note'      => __('One method name per line, e.g Next Day Air'),
        ));

        $promoShippingRulesUrl = $this->getUrl('sales_rule/promo_quote');
        $promoShippingRulesUrl = '<a href="'.$promoShippingRulesUrl.'">'.__('Promotions / Shopping Cart Rules').'</a>';

        $fieldset->addField('coupon', 'text', array(
            'label'     => __('Coupon Code'),
            'title'     => __('Coupon Code'),
            'name'      => 'coupon',
            'note'      => __('Apply this rule with coupon only. Create coupon in %1 area first. Useful when you have ONE coupon only.', $promoShippingRulesUrl),
        ));


        $fieldset->addField('discount_id', 'select', array(
            'label'     => __('Shopping Cart Rule (discount)'),
            'name'      => 'discount_id',
            'values'    => $hlp->getAllRules(),
            'note'      => __('Apply this rule with ANY coupon from specified discount rule. Create rule in %1 area first. Useful when you have MULTIPLE coupons in one rule.', $promoShippingRulesUrl),
        ));

        $fieldset->addField('days', 'multiselect', array(
            'label'     => __('Days of the week'),
            'name'      => 'days[]',
            'values'    => $hlp->getAllDays(),
            'note'      => __('Apply the rules for selected days of week only. Set empty for all days.'),
        ));


        $fieldset->addField('pos', 'text', array(
            'label'     => __('Priority'),
            'name'      => 'pos',
            'note'      => __('If a product matches several rules, the first rule will be applied only.'),
        ));

        $form->setValues($model->getData());
        $form->addValues(['id'=>$model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}