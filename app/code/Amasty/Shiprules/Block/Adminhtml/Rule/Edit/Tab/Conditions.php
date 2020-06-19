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
use Amasty\Shiprules\Model\Rule;


class Conditions extends Generic implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $_rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $_conditions;

    /**
     * @var \Amasty\Shiprules\Model\Source\Backorders
     */
    protected $backordersSource;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Amasty\Shiprules\Model\Source\Backorders $backordersSource,
        array $data = []
    ) {
        $this->_rendererFieldset = $rendererFieldset;
        $this->_conditions = $conditions;
        $this->backordersSource = $backordersSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Address Conditions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Address Conditions');
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
            $this->getUrl('*/*/newConditionHtml/form/rule_conditions_fieldset')
        );

        $fieldset = $form->addFieldset(
            'rule_conditions_fieldset',
            [
                'legend' => __(
                    'Apply the rule only if the following conditions are met (leave blank for all products).'
                )
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'conditions',
            'text',
            ['name' => 'conditions', 'label' => __('Conditions'), 'title' => __('Conditions')]
        )->setRule(
            $model
        )->setRenderer(
            $this->_conditions
        );

        $fldAdv = $form->addFieldset('advanced', array('legend'=> __('Backorders')));
        $fldAdv->addField('out_of_stock', 'select', array(
            'label'     => __('Apply the rule to'),
            'name'      => 'out_of_stock',
            'options'   => $this->backordersSource->toArray(),
        ));
        
        $form->setValues($model->getData());
        $form->addValues(['id'=>$model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
