<?php

class Keycash_Core_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('keycash_core/keycash_order', 'order_id');
    }

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
