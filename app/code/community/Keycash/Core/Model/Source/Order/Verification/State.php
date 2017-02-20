<?php
/**
 * KeyCash
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Keycash
 * @package     Keycash_Core
 * @copyright   Copyright (c) 2017 KeyCash. (https://keycash.co)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Keycash_Core_Model_Source_Order_Verification_State
{
    /**
     * Verification state codes
     */
    const UNATTEMPTED = 'unattempted';
    const UNVERIFIED = 'unverified';
    const VERIFIED = 'complete';

    /**
     * @param bool $valueAsKey
     * @return array
     */
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

    /**
     * @return array
     */
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
