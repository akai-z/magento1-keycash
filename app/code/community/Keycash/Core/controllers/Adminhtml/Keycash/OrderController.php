<?php

class Keycash_Core_Adminhtml_Keycash_OrderController extends Mage_Adminhtml_Controller_Action
{
    public function verifyAction()
    {
        $orderId = null;
        if ($this->getRequest()->getParam('keycash_order_id')) {
            $orderId = array(
                'keycash_order_id' => $this->getRequest()->getParam('keycash_order_id')
            );
        } elseif ($this->getRequest()->getParam('order_id')) {
            $orderId = array(
                'order_id' => $this->getRequest()->getParam('order_id')
            );
        }

        if ($orderId) {
            $this->verifyOrder($orderId);
        } else {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'This order cannot be verified.'
                )
            );
        }

        $this->_redirectReferer();
    }

    public function massVerifyAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids');

        if ($orderIds) {
            $helper = Mage::helper('keycash_core');
            $verificationLimit = $helper->getSendOrdersLimit();

            if (
                $verificationLimit
                && (count($orderIds) > $helper->getSendOrdersLimit())
            ) {
                $this->_getSession()->addError(
                    $helper->__(
                        'Maximum allowed orders per request is %s.',
                        $verificationLimit
                    )
                );

                $this->_redirectReferer();
                return;
            }

            $closedOrderStatuses = Mage::getModel(
                'keycash_core/source_order_status_closed'
            )->toOptionArray(true);

            $customCanceledOrderStatuses = $helper->getCustomCanceledOrderStatuses();
            if ($customCanceledOrderStatuses) {
                $closedOrderStatuses = array_unique(
                    array_merge(
                        $closedOrderStatuses,
                        $customCanceledOrderStatuses
                    )
                );
            }

            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('entity_id', array('in' => $orderIds))
                ->addAttributeToFilter('relation_child_id', array('null' => true))
                ->addAttributeToFilter(
                    'status',
                    array('nin' => $closedOrderStatuses)
                );

            if (!$orderCollection->getSize()) {
                $this->_getSession()->addError(
                    $helper->__(
                        'Only open orders can be verified.'
                    )
                );

                $this->_redirectReferer();
                return;
            }
        } else {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'Please select orders to verify.'
                )
            );

            $this->_redirectReferer();
            return;
        }

        $orderIds = array();
        foreach ($orderCollection as $order) {
            $orderIds[] = $order->getEntityId();
        }

        if (count($orderIds) == 1) {
            $this->verifyOrder(
                array('order_id' => reset($orderIds))
            );

            $this->_redirectReferer();
            return;
        }

        Mage::getModel('keycash_core/apirequest')
            ->setRequestName('mass_order_verification')
            ->setRequestData(serialize($orderIds))
            ->save();

        $this->_getSession()->addSuccess(
            Mage::helper('keycash_core')->__(
                'A KeyCash verification request has been set for the selected orders.'
            )
        );

        $this->_redirectReferer();
    }

    public function getVerificationStatusAction()
    {
        $orderId = $this->getRequest()->getParam('keycash_order_id');

        if (!$orderId) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'The selected request cannot be applied on the current order.'
                )
            );

            $this->_redirectReferer();
            return;
        }

        $order = Mage::getModel('keycash_core/order')
            ->loadByKeycashOrderId($orderId);

        if (!$order->getId()) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'Could not find KeyCash order in your store.'
                    . ' If the issue persists, please report it to KeyCash.'
                )
            );

            $this->_redirectReferer();
            return;
        }
        if (
            $order->getVerificationState() ==
            Keycash_Core_Model_Source_Order_Verification_State::UNATTEMPTED
        ) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'In order to retrieve verification status,'
                    . ' an order verification request must be placed first.'
                )
            );

            $this->_redirectReferer();
            return;
        }
        if ($order->getIsVerified()) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'Order is already verified.'
                )
            );

            $this->_redirectReferer();
            return;
        }

        $result = Mage::getModel('keycash_core/apirequest')
            ->executeOrderRetrieveRequest($orderId, true);

        if (!$result) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'Could not retrieve order details from KeyCash.'
                    . ' If the issue persists, please report it to KeyCash.'
                )
            );

            $this->_redirectReferer();
            return;
        }

        $update = false;
        if ($result['is_verified']) {
            $order->setIsVerified($result['is_verified']);

            if (!isset($result['verification_state'])) {
                $order->setVerificationState('verified')
                    ->setVerificationStatus('Order is verified');
            } else {
                $order->setVerificationDate($result['verification_date']);
            }

            $update = true;
        }

        if (isset($result['verification_state'])) {
            $order->setVerificationState($result['verification_state'])
                ->setVerificationStatus($result['verification_status'])
                ->setVerificationStrategy($result['verification_strategy']);

            $update = true;
        }

        if ($update) {
            $order->save();

            $this->_getSession()->addSuccess(
                Mage::helper('keycash_core')->__(
                    'Order KeyCash verification status has been updated.'
                )
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'There are no order verification status updates to retrieve.'
                )
            );
        }

        $this->_redirectReferer();
    }

    protected function verifyOrder($orderId)
    {
        $result = '';

        if (isset($orderId['order_id'])) {
            $orderId = $orderId['order_id'];
            $data = Mage::getModel('keycash_core/order')
                ->getApiOrderCreationData($orderId);

            $orderCreationResult = Mage::getModel('keycash_core/apirequest')
                ->executeOrderCreation($data);

            if (!$orderCreationResult) {
                $this->_getSession()->addError(
                    Mage::helper('keycash_core')->__(
                        'Could not submit order data to KeyCash.'
                        . ' If the issue persists, please report it to KeyCash.'
                    )
                );

                return $result;
            }

            $keycashOrderData = $orderCreationResult;
            $keycashOrderId = $keycashOrderData['keycash_order_id'];

            Mage::getModel('keycash_core/order')->setData($keycashOrderData)->save();
        } elseif (isset($orderId['keycash_order_id'])) {
            $keycashOrderId = $orderId['keycash_order_id'];
        }

        if (!$keycashOrderId) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'Could not read order ID.'
                    . ' If the issue persists, please report it to KeyCash.'
                )
            );

            return $result;
        }

        $keycashOrder = Mage::getModel('keycash_core/order')
            ->loadByKeycashOrderId($keycashOrderId);

        if (
            $keycashOrder->getVerificationState() !=
            Keycash_Core_Model_Source_Order_Verification_State::UNATTEMPTED
        ) {
            $this->_getSession()->addError(
                Mage::helper('keycash_core')->__(
                    'An order verification request has already been submitted.'
                )
            );

            return $result;
        }

        $result = Mage::getModel('keycash_core/apirequest')
            ->executeOrderVerification($keycashOrderId);

        if (!$result) {
            $errorMessage = Mage::helper('keycash_core')->__(
                'Could not verify order.'
                . ' If the issue persists, please report it to KeyCash.'
            );

            return $result;
        }

        if (isset($result['status']) && !$result['status']) {
            $alreadyVerifiedOrderResponseCode = Mage::helper('keycash_core/api')
                ->getAlreadyVerifiedOrderResponseCode();

            if ($result['code'] == $alreadyVerifiedOrderResponseCode) {
                $errorMessage = Mage::helper('keycash_core')->__(
                    'An order verification request has already been submitted.'
                );
            } else {
                $errorMessage = Mage::helper('keycash_core')->__(
                    'Could not verify order.'
                    . ' If the issue persists, please report it to KeyCash.'
                );
            }

            $this->_getSession()->addError($errorMessage);
        } else {
            $keycashOrder = Mage::getModel('keycash_core/order')
                ->loadByKeycashOrderId($keycashOrderId);

            $keycashOrder
                ->setVerificationState($result['verification_state'])
                ->setVerificationStatus($result['verification_status'])
                ->setVerificationStrategy($result['verification_strategy'])
                ->save();

            $this->_getSession()->addSuccess(
                Mage::helper('keycash_core')->__(
                    'Order verification request has been submitted.'
                )
            );
        }

        return $result;
    }

    protected function _isAllowed()
    {
        $helper = Mage::helper('keycash_core');

        return $helper->isEnabled()
            ? Mage::getSingleton('admin/session')->isAllowed($this->getAclResource())
            : false;
    }

    protected function getAclResource()
    {
        $action = strtolower($this->getRequest()->getActionName());
        $aclResource = 'sales/order/keycash';

        switch ($action) {
            case 'getverificationstatus':
                $aclResource .= '/get_verification_status';
                break;
            case 'verify':
                if (Mage::helper('keycash_core')->isSendOrdersEnabled()) {
                    $aclResource .= '/verify';
                } else {
                    $aclResource = '';
                }

                break;
            case 'massverify':
                if (Mage::helper('keycash_core')->isSendOrdersEnabled()) {
                    $aclResource .= '/mass_verify';
                } else {
                    $aclResource = '';
                }

                break;
        }

        return $aclResource;
    }
}
