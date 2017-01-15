<?php

class Keycash_Core_Model_System_Config_Source_Order_Status
    extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    protected $_stateStatuses = null;

    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        unset($options[0]);

        return $options;
    }
}
