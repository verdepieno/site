<?php


namespace Webgriffe\QuiPago\Model\Config\Source;

class HashingMethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Webgriffe\LibQuiPago\PaymentInit\UrlGenerator
     */
    private $quipagoUrlGenerator;

    /**
     * HashingMethods constructor.
     * @param $quipagoUrlGenerator
     */
    public function __construct(\Webgriffe\QuiPago\PaymentInit\UrlGenerator $quipagoUrlGenerator)
    {
        $this->quipagoUrlGenerator = $quipagoUrlGenerator;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $toOptionArray = [];
        foreach ($this->quipagoUrlGenerator->getAllowedMacCalculationMethods() as $method => $label) {
            $toOptionArray[] = ['value' => $method, 'label' => __($label)];
        }
        return $toOptionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $toArray = [];
        foreach ($this->quipagoUrlGenerator->getAllowedMacCalculationMethods() as $method => $label) {
            $toArray[$method] = __($label);
        }
        return $toArray;
    }
}
