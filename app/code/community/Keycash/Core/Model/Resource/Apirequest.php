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
// @codingStandardsIgnoreStart
class Keycash_Core_Model_Resource_Apirequest extends Mage_Core_Model_Resource_Db_Abstract
{
    // @codingStandardsIgnoreEnd

    protected function _construct()
    {
        $this->_init('keycash_core/scheduled_api_requests', 'request_id');
    }

    /**
     * @param int|array $id
     */
    public function delete($id)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete(
            $this->getMainTable(),
            array('request_id IN (?)' => $id)
        );
    }
}
