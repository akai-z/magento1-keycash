<?php

class Keycash_Core_Model_Source_Order_Verification_State
{
    const UNATTEMPTED = 'unattempted';
    const UNVERIFIED = 'unverified';
    const VERIFIED = 'verified';

    public function toOptionArray($valueAsKey = false)
    {
        $helper = Mage::helper('keycash_core');

        $options = array(
            array(
                'value' => self::UNVERIFIED,
                'label' => $helper->__('Unverified')
            ),
            array(
                'value' => self::VERIFIED,
                'label' => $helper->__('Verified'),
                'icon' => 'keycash/core/images/verified.png'
            )
        );

        if ($valueAsKey) {
            $alternativeFormat = array();
            foreach ($options as $option) {
                $alternativeFormat[$option['value']] = array(
                    'label' => $option['label']
                );

                if (isset($option['icon'])) {
                    $alternativeFormat[$option['value']]['icon'] = $option['icon'];
                }
            }

            $options = $alternativeFormat;
        }

        return $options;
    }

    public function getGridFilterOptions()
    {
        $options = array();

        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    public function getFlatOptions()
    {
        $options = array();
        $rawOptions = $this->toOptionArray();

        foreach ($rawOptions as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }
}
