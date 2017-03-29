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
 * @copyright   Copyright (c) 2017 KeyCash. (https://www.keycash.co/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Keycash_Core_Model_Source_Order_Verification_State
{
    /**
     * Verification state codes
     */
     const NOT_DISPATCHED = 'not_dispatched';
     const IN_PROGRESS = 'in_progress';
     const ERROR = 'error';
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
                'value' => self::NOT_DISPATCHED,
                'label' => $helper->__('Not Dispatched'),
                'icon' => 'keycash/core/images/not_dispatched.png'
            ),
            array(
                'value' => self::IN_PROGRESS,
                'label' => $helper->__('In Progress'),
                'icon' => 'keycash/core/images/in_progress.png'
            ),
            array(
                'value' => self::ERROR,
                'label' => $helper->__('Error'),
                'icon' => 'keycash/core/images/error.png'
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
