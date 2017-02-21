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

/**
 * Customer order verification view info block
 *
 * @category    Keycash
 * @package     Keycash_Core
 */
class Keycash_Core_Block_Sales_Order_View_Info extends Mage_Core_Block_Template
{
    /**
     * @var Keycash_Core_Model_Order
     */
    protected $keycashOrder;

    protected function _construct()
    {
        $helper = Mage::helper('keycash_core');

        if (!$helper->isEnabled() || $this->getOrder()->getRelationChildId()) {
            return;
        }

        $this->keycashOrder = $this->getKeycashOrder();

        if (!$this->keycashOrder->getId()) {
            return;
        }

        parent::_construct();
        $this->setTemplate('keycash/core/sales/order/view/info.phtml');
    }

    /**
     * Retrieve KeyCash order verification state
     *
     * @return string
     */
    public function getVerificationState()
    {
        $verificationState = $this->getOrder()->getVerificationState();

        if (!$verificationState) {
            $verificationStates = Mage::getModel(
                'keycash_core/source_order_verification_state'
            )->getFlatOptions();

            $verificationState = reset($verificationStates);
        } else {
            $verificationState = ucwords(str_replace('_', ' ', $verificationState));
        }

        return $verificationState;
    }

    /**
     * Retrieves current sales order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Retrieves KeyCash order associated with a sales order
     *
     * @return Keycash_Core_Model_Order
     */
    protected function getKeycashOrder()
    {
        $order = $this->getOrder();
        $keycashOrder = Mage::getModel('keycash_core/order')
            ->loadBySalesOrderId($order->getEntityId());

        return $keycashOrder;
    }
}
