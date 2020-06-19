<?php


namespace Webgriffe\QuiPago\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Webgriffe\QuiPago\Model\PaymentMethod as QuiPagoPaymentMethod;

/**
 *
 * QuiPago Payment Action Dropdown source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => QuiPagoPaymentMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => QuiPagoPaymentMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
