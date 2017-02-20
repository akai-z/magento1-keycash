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

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$keycashOrderTableName = $installer->getTable('keycash_core/keycash_order');

$installer->getConnection()->changeColumn(
    $keycashOrderTableName,
    'verification_state',
    'verification_state',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 128,
        'nullable' => true,
        'default' => 'not_dispatched',
        'comment' => 'Order Verification State'
    )
);

$installer->getConnection()->update(
    $keycashOrderTableName,
    array(
        'verification_state' => 'not_dispatched'
    ),
    array(
        'verification_state = ?' => 'unattempted'
    )
);

$installer->endSetup();
