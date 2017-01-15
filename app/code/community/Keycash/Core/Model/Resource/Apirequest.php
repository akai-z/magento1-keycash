<?php

class Keycash_Core_Model_Resource_Apirequest extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('keycash_core/scheduled_api_requests', 'request_id');
    }
}
