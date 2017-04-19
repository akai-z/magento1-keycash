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
class Keycash_Core_Model_System_Config_Source_Order_Status
    extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
    // @codingStandardsIgnoreEnd
    /**
     * set null to enable all possible
     *
     * @var array|null
     */
    protected $_stateStatuses = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        unset($options[0]);

        return $options;
    }
}
