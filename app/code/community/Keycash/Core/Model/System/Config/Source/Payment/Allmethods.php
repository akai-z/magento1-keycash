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
class Keycash_Core_Model_System_Config_Source_Payment_Allmethods
    extends Mage_Adminhtml_Model_System_Config_Source_Payment_Allmethods
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $methods = parent::toOptionArray();

        foreach ($methods as $i => $method) {
            if ($method['value'] == 'googlecheckout') {
                $methods[$i]['label'] = Mage::helper('keycash_core')->__('Google Checkout');

                break;
            }
        }

        return $methods;
    }
}
