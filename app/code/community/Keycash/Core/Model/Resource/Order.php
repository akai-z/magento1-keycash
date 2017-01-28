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
class Keycash_Core_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('keycash_core/keycash_order', 'order_id');
    }

    /**
     * Inserts multiple records into DB table in one run
     *
     * @param array $data
     * @param bool $onDuplicate
     */
    public function insertMultiple($data, $onDuplicate = false)
    {
        if ($onDuplicate) {
            $this->_getWriteAdapter()->insertOnDuplicate(
                $this->getTable('keycash_core/keycash_order'),
                $data
            );
        } else {
            $this->_getWriteAdapter()->insertMultiple(
                $this->getTable('keycash_core/keycash_order'),
                $data
            );
        }
    }

    /**
     * @param int $orderId
     * @return false|array
     */
    public function loadBySalesOrderId($orderId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('sales_order_id = :sales_order_id');

        $binds = array(
            'sales_order_id' => $orderId
        );

        return $adapter->fetchRow($select, $binds);
    }

    /**
     * @param int $orderId
     * @return false|array
     */
    public function loadByKeycashOrderId($orderId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('keycash_order_id = :keycash_order_id');

        $binds = array(
            'keycash_order_id' => $orderId
        );

        return $adapter->fetchRow($select, $binds);
    }

    /**
     * @param int $incrementId
     * @return false|array
     */
    public function loadByIncrementId($incrementId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('increment_id = :increment_id');

        $binds = array(
            'increment_id' => $incrementId
        );

        return $adapter->fetchRow($select, $binds);
    }
}
