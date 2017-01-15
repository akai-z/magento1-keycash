<?php

class Keycash_Core_Model_System_Config_Source_Payment_Allmethods
    extends Mage_Adminhtml_Model_System_Config_Source_Payment_Allmethods
{
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
