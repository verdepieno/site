<?php


namespace Webgriffe\QuiPago\Model\Config\Source;

class Languages implements \Magento\Framework\Option\ArrayInterface
{
    const LANGUAGE_ITA = 'ITA';
    const LANGUAGE_ENG = 'ENG';
    const LANGUAGE_SPA = 'SPA';
    const LANGUAGE_FRA = 'FRA';
    const LANGUAGE_GER = 'GER';

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::LANGUAGE_ITA, 'label' => __('Italian')],
            ['value' => self::LANGUAGE_ENG, 'label' => __('English')],
            ['value' => self::LANGUAGE_SPA, 'label' => __('Spanish')],
            ['value' => self::LANGUAGE_FRA, 'label' => __('French')],
            ['value' => self::LANGUAGE_GER, 'label' => __('German')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            [self::LANGUAGE_ITA => __('Italian')],
            [self::LANGUAGE_ENG => __('English')],
            [self::LANGUAGE_SPA => __('Spanish')],
            [self::LANGUAGE_FRA => __('French')],
            [self::LANGUAGE_GER => __('German')],
        ];
    }
}
