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
 * Order verification tab block
 *
 * @category    Keycash
 * @package     Keycash_Core
 */
class Keycash_Core_Block_Adminhtml_Sales_Order_View_Tab_Verification
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @var bool
     */
    protected $canShowTab = true;

    /**
     * @var Keycash_Core_Model_Order
     */
    protected $keycashOrder;

    /**
     * Specifies whether an order has a closed status
     *
     * @var bool
     */
    protected $isClosedOrder = false;

    protected function _construct()
    {
        $helper = Mage::helper('keycash_core');
        $order = $this->getOrder();

        if (!$helper->isEnabled() || $order->getRelationChildId()) {
            $this->canShowTab = false;
            return;
        }

        $isOrderVerifiable = Mage::getModel('keycash_core/order')
            ->isOrderVerifiable($order, false);

        if (!$isOrderVerifiable) {
            $this->canShowTab = false;
            return;
        }

        $this->keycashOrder = $this->getKeycashOrder();

        $acceptableOrderStatuses = Mage::getModel(
            'keycash_core/source_order_status_closed'
        )->getAcceptableStatuses();

        $this->isClosedOrder = in_array(
            $this->getOrder()->getStatus(),
            $acceptableOrderStatuses
        );

        if (!$this->keycashOrder->getId() && $this->isClosedOrder) {
            $this->canShowTab = false;
            return;
        }

        parent::_construct();
        $this->setTemplate('keycash/core/sales/order/view/tab/verification.phtml');
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

    /**
     * Retrieve KeyCash order verification data
     *
     * @return array
     */
    public function getVerificationData()
    {
        $data = array();
        $order = $this->keycashOrder;

        if (!$order->getId()) {
            return $data;
        }

        $helper = Mage::helper('keycash_core');

        $verificationState = $order->getVerificationState();
        if (!$verificationState) {
            $verificationStates = Mage::getModel(
                'keycash_core/source_order_verification_state'
            )->getFlatOptions();

            $verificationState = reset($verificationStates);
        } else {
            $verificationState = ucwords(str_replace('_', ' ', $verificationState));
        }

        $data[] = array(
            'label' => $helper->__('Verification State'),
            'value' => $verificationState
        );

        if ($order->getVerificationStatus()) {
            $data[] = array(
                'label' => $helper->__('Verification Status'),
                'value' => $order->getVerificationStatus()
            );
        }
        if ($order->getVerificationStrategy()) {
            $data[] = array(
                'label' => $helper->__('Verification Strategy'),
                'value' => ucfirst($order->getVerificationStrategy())
            );
        }
        if ($order->getVerificationDate()) {
            $data[] = array(
                'label' => $helper->__('Verification Date'),
                'value' => $order->getVerificationDate()
            );
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function isVerificationStatusRetrieveButtonEnabled()
    {
        if ($this->isClosedOrder) {
            return false;
        }

        if ($this->keycashOrder->getId()) {
            if (
                $this->keycashOrder->getIsVerified()
                || $this->keycashOrder->getVerificationState() ==
                Keycash_Core_Model_Source_Order_Verification_State::NOT_DISPATCHED
            ) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getVerificationStatusRetrieveRequestUrl()
    {
        $param = array(
            'keycash_order_id' => $this->keycashOrder->getKeycashOrderId()
        );

        return $this->getUrl('adminhtml/keycash_order/getverificationstatus', $param);
    }

    /**
     * @return bool
     */
    public function isOrderVerifyButtonEnabled()
    {
        if ($this->isClosedOrder) {
            return false;
        }

        if ($this->keycashOrder->getId()) {
            if (
                $this->keycashOrder->getIsVerified()
                || $this->keycashOrder->getVerificationState() !=
                Keycash_Core_Model_Source_Order_Verification_State::NOT_DISPATCHED
            ) {
                return false;
            } else {
                return true;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getOrderVerifyUrl()
    {
        $params = array();
        if ($this->keycashOrder->getId()) {
            if (!$this->keycashOrder->getIsVerified()) {
                $param = array(
                    'keycash_order_id' => $this->keycashOrder->getKeycashOrderId()
                );
            }
        } else {
            $param = array(
                'order_id' => $this->getOrder()->getEntityId()
            );
        }

        return $this->getUrl('adminhtml/keycash_order/verify', $param);
    }

    /**
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('keycash_core')->__('KeyCash Verification');
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('keycash_core')->__('KeyCash Order Verification');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return $this->canShowTab;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
