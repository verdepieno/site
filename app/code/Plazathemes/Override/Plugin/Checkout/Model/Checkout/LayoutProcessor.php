<?php
namespace Plazathemes\Override\Plugin\Checkout\Model\Checkout;
class LayoutProcessor
{
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['vat_id'] = [
			'component' => 'Magento_Ui/js/form/element/abstract',
			'config' => [
				'customScope' => 'shippingAddress',
				'template' => 'ui/form/field',
				'elementTmpl' => 'ui/form/element/input',
				'id' => 'vat_id',
			],
			'provider' => 'checkoutProvider',
			'dataScope' => 'shippingAddress.vat_id',
			'label' => 'P.IVA',
			'sortOrder' => 252,
			'visible' => true,
			'id' => 'vat_id',
			'validation' => [
				'required-entry' => false,
			],
		];
		return $jsLayout;
    }
}
