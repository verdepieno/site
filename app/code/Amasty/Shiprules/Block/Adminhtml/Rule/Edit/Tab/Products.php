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


class Products extends Generic implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $_rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Actions
     */
    protected $_actions;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Actions $actions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        array $data
    ) {
        $this->_rendererFieldset = $rendererFieldset;
        $this->_actions = $actions;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Products');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Products');
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
        //$form->setHtmlIdPrefix('rule_');

        $renderer = $this->_rendererFieldset->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $this->getUrl('*/*/newActionHtml/form/rule_actions_fieldset')
        );

        $fieldset = $form->addFieldset(
            'rule_actions_fieldset',
            [
                'legend' => __(
                    'Select products or leave blank for all products.'
                )
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'actions',
            'text',
            ['name' => 'actions', 'label' => __('Conditions'), 'title' => __('Conditions')]
        )->setRule(
            $model
        )->setRenderer(
            $this->_actions
        );

        $fldFree = $form->addFieldset('free', array('legend'=> __('Free Shipping')));
        $fldFree->addField('ignore_promo', 'select', array(
            'label'     => __('Ignore Free Shipping Promo'),
            'title'     => __('Ignore Free Shipping Promo'),
            'name'      => 'ignore_promo',
            'options'   => array(
                '0' => __('No'),
                '1' => __('Yes'),
            ),
            'note'      => __('If the option is set to `No`, totals below will be applied only to items with non-free shipping.'),
        ));


        $fldTotals = $form->addFieldset('totals', array('legend'=> __('Totals for selected products, excluding items shipped for free.')));
        $fldTotals->addField('weight_from', 'text', array(
            'label'     => __('Weight From'),
            'title'     => __('Weight From'),
            'name'      => 'weight_from',
        ));
        $fldTotals->addField('weight_to', 'text', array(
            'label'     => __('Weight To'),
            'title'     => __('Weight To'),
            'name'      => 'weight_to',
        ));

        $fldTotals->addField('qty_from', 'text', array(
            'label'     => __('Qty From'),
            'title'     => __('Qty From'),
            'name'      => 'qty_from',
        ));
        $fldTotals->addField('qty_to', 'text', array(
            'label'     => __('Qty To'),
            'title'     => __('Qty To'),
            'name'      => 'qty_to',
        ));

        $fldTotals->addField('price_from', 'text', array(
            'label'     => __('Price From'),
            'title'     => __('Price From'),
            'name'      => 'price_from',
            'note'      => __('Original product cart price, without discounts.'),
        ));
        $fldTotals->addField('price_to', 'text', array(
            'label'     => __('Price To'),
            'title'     => __('Price To'),
            'name'      => 'price_to',
            'note'      => __('Original product cart price, without discounts.'),
        ));

        $form->setValues($model->getData());
        $form->addValues(['id'=>$model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}