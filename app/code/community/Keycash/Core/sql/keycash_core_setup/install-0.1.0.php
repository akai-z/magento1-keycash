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

/**
 * @var $installer Mage_Core_Model_Resource_Setup
 */
$installer = $this;
$installer->startSetup();

$keycashOrderTableName = 'keycash_core/keycash_order';
$keycashOrderTableFullName = $installer->getTable($keycashOrderTableName);
$salesOrderTableName = $installer->getTable('sales/order');
$scheduledKeyVerifyApiRequestsTableName = $installer->getTable('keycash_core/scheduled_api_requests');

Mage::helper('keycash_core')->setPublicIp(
    Mage::helper('core/http')->getServerAddr()
);

/**
 * Create table 'keycash_core/keycash_order'
 */
$orderVerificationTable = $installer->getConnection()
    ->newTable($keycashOrderTableFullName)
    ->addColumn(
        'order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ),
        'KeyCash Order ID'
    )
    ->addColumn(
        'sales_order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'nullable' => false
        ),
        'Sales Order ID'
    )
    ->addColumn(
        'keycash_order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'nullable' => false
        ),
        'KeyCash API Order ID'
    )
    ->addColumn(
        'increment_id',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        50,
        array(
            'nullable' => false
        ),
        'Sales Order Increment ID'
    )
    ->addColumn(
        'verification_state',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        128,
        array(
            'nullable' => true,
            'default' => 'unattempted'
        ),
        'Order Verification State'
    )
    ->addColumn(
        'verification_status',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        255,
        array(
            'nullable' => true
        ),
        'Order Verification Status'
    )
    ->addColumn(
        'verification_strategy',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        128,
        array(
            'nullable'  => true
        ),
        'Order Verification Strategy'
    )
    ->addColumn(
        'verification_date',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(
            'nullable'  => true
        ),
        'Order Verification Date'
    )
    ->addColumn(
        'is_verified',
        Varien_Db_Ddl_Table::TYPE_SMALLINT,
        null,
        array(
            'unsigned' => true,
            'nullable' => false,
            'default'   => '0'
        ),
        'Is Order Verified'
    )
    ->addIndex(
        $installer->getIdxName(
            $keycashOrderTableName,
            array('sales_order_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('sales_order_id'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex(
        $installer->getIdxName(
            $keycashOrderTableName,
            array('keycash_order_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('keycash_order_id'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex(
        $installer->getIdxName(
            $keycashOrderTableName,
            array('increment_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('increment_id'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addForeignKey(
        $installer->getFkName($keycashOrderTableFullName, 'sales_order_id', $salesOrderTableName, 'entity_id'),
        'sales_order_id', $salesOrderTableName, 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('KeyCash Sales Order Table');

$installer->getConnection()->createTable($orderVerificationTable);

/**
 * Create table 'keycash_core/scheduled_api_requests'
 */
$scheduledKeyVerifyApiRequestsTable = $installer->getConnection()
    ->newTable($scheduledKeyVerifyApiRequestsTableName)
    ->addColumn(
        'request_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ),
        'Request ID'
    )
    ->addColumn(
        'request_name',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        128,
        array(
            'nullable' => false
        ),
        'Request Name'
    )
    ->addColumn(
        'request_data',
        Varien_Db_Ddl_Table::TYPE_BLOB,
        null,
        array(
            'nullable'  => false
        ),
        'Request Data'
    )
    ->setComment('KeyCash Scheduled KeyVerify API Requests Table');

$installer->getConnection()->createTable($scheduledKeyVerifyApiRequestsTable);

$installer->endSetup();
