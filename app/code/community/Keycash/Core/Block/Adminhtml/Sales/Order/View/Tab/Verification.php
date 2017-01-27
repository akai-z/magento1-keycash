<?php

class Keycash_Core_Block_Adminhtml_Sales_Order_View_Tab_Verification
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $canShowTab = true;

    protected $keycashOrder;

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

        parent::_construct();
        $this->setTemplate('keycash/core/sales/order/view/tab/verification.phtml');
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    protected function getKeycashOrder()
    {
        $order = $this->getOrder();
        $keycashOrder = Mage::getModel('keycash_core/order')
            ->loadBySalesOrderId($order->getEntityId());

        return $keycashOrder;
    }

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
            $verificationState = ucfirst($verificationState);
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

    public function isVerificationStatusRetrieveButtonEnabled()
    {
        if ($this->isClosedOrder) {
            return false;
        }

        if ($this->keycashOrder->getId()) {
            if (
                $this->keycashOrder->getIsVerified()
                || $this->keycashOrder->getVerificationState() ==
                Keycash_Core_Model_Source_Order_Verification_State::UNATTEMPTED
            ) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    public function getVerificationStatusRetrieveRequestUrl()
    {
        $param = array(
            'keycash_order_id' => $this->keycashOrder->getKeycashOrderId()
        );

        return $this->getUrl('adminhtml/keycash_order/getverificationstatus', $param);
    }

    public function isOrderVerifyButtonEnabled()
    {
        if ($this->isClosedOrder) {
            return false;
        }

        if ($this->keycashOrder->getId()) {
            if (
                $this->keycashOrder->getIsVerified()
                || $this->keycashOrder->getVerificationState() !=
                Keycash_Core_Model_Source_Order_Verification_State::UNATTEMPTED
            ) {
                return false;
            } else {
                return true;
            }
        }

        return true;
    }

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

    public function getTabLabel()
    {
        return Mage::helper('keycash_core')->__('KeyCash Verification');
    }

    public function getTabTitle()
    {
        return Mage::helper('keycash_core')->__('KeyCash Order Verification');
    }

    public function canShowTab()
    {
        return $this->canShowTab;
    }

    public function isHidden()
    {
        return false;
    }
}
