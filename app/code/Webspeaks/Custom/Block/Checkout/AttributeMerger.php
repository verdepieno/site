<?php
namespace Webspeaks\Custom\Block\Checkout;

class AttributeMerger
{
	 /**
     * Create correct date field
     *
     * @return string
     */
    public function beforeMerge($subject, $elements, $providerName, $dataScopePrefix, array $fields = [])
    {
    	if (isset($elements['vat_id'])) {
            $elements['vat_id']['label'] = 'Inserire P.IVA se fattura';
    	}
        return [$elements, $providerName, $dataScopePrefix, $fields];
    }

}