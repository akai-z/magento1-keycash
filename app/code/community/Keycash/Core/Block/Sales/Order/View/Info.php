<?php

class Keycash_Core_Block_Sales_Order_View_Info extends Mage_Core_Block_Template
{
    protected $keycashOrder;

    protected function _construct()
    {
        $helper = Mage::helper('keycash_core');

        if (!$helper->isModuleEnabled() || $this->getOrder()->getRelationChildId()) {
            return;
        }

        $this->keycashOrder = $this->getKeycashOrder();

        if (!$this->keycashOrder->getId()) {
            return;
        }

        parent::_construct();
        $this->setTemplate('keycash/core/sales/order/view/info.phtml');
    }

    public function getVerificationState()
    {
        $verificationState = $this->getOrder()->getVerificationState();

        if (!$verificationState) {
            $verificationStates = Mage::getModel(
                'keycash_core/source_order_verification_state'
            )->getFlatOptions();

            $verificationState = reset($verificationStates);
        } else {
            $verificationState = ucfirst($verificationState);
        }

        return $verificationState;
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
}
