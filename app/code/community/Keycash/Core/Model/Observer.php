<?php

class Keycash_Core_Model_Observer
{
    public function checkCronHeartbeatStatus(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled()) {
            return;
        }

        $lastHeartbeat = $helper->getLastCronHeartbeat();
        if ($lastHeartbeat) {
            $heatbeatAge = Mage::getModel('core/date')->gmtTimestamp() - $lastHeartbeat;
            if ($heatbeatAge <= $helper->getCronHeartbeatInterval()) {
                return;
            }
        }

        if ($lastNotificationId = $helper->getCronHeartbeatWarningNotification()) {
            $notificationModel = Mage::getModel('adminnotification/inbox');
            $notification = $notificationModel->load($lastNotificationId);
            if (!$notification->getIsRemove()) {
                $notification->setIsRemove(1)->save();
            }
        }

        $notificationModel = Mage::getModel('adminnotification/inbox');
        $notificationModel->addMajor(
            $helper->__('KeyCash service is inactive'),
            $helper->__('KeyCash service is inactive, please make sure that Cron is running.')
        );

        $notification = $notificationModel->loadLatestNotice();
        $helper->setCronHeartbeatWarningNotification($notification->getNotificationId());
    }

    public function updatePublicIp()
    {
        $helper = Mage::helper('keycash_core');
        $ip = Mage::helper('core/http')->getServerAddr();

        if ($helper->getPublicIp() != $ip) {
            $helper->setPublicIp($ip);
        }
    }

    public function addKeycashVerificationStateToSalesOrderGridCollection(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('keycash_core')->isModuleEnabled()) {
            return;
        }

        $collection = $observer->getEvent()->getOrderGridCollection();
        if (!isset($collection)) {
            return;
        }

        if ($collection instanceof Mage_Sales_Model_Resource_Order_Collection) {
            $keycashOrderTable = Mage::getSingleton('core/resource')
                ->getTableName('keycash_core/keycash_order');

            $collection->getSelect()->joinLeft(
                $keycashOrderTable,
                'main_table.entity_id = ' . $keycashOrderTable . '.sales_order_id',
                array('keycash_verification_state' => $keycashOrderTable . '.verification_state')
            );
        }
    }

    public function addKeycashVerificationStateColumnToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled()) {
            return;
        }

        $block = $observer->getBlock();
        if (!isset($block)) {
            return;
        }

        if ($block->getType() == 'adminhtml/sales_order_grid') {
            $verificationStateFilterBlock =
                'Keycash_Core_Block_Adminhtml_Sales_Order_Grid_Column_Filter_Verificationstate';

            $block->addColumnAfter('keycash_verification_state', array(
                'header'   => $helper->__('KeyCash Verification Status'),
                'renderer' => 'keycash_core/adminhtml_sales_order_grid_column_renderer_verificationstate',
                'align'    => 'center',
                'type'     => 'options',
                'index'    => 'keycash_verification_state',
                'options'  => Mage::getModel('keycash_core/source_order_verification_state')->getGridFilterOptions(),
                'filter_condition_callback' => array($verificationStateFilterBlock, 'filterOrderByVerificationState')
            ), 'real_order_id');

            $block->sortColumnsByOrder();
        }

    }

    public function addKeycashOrdersMassVerificationToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled()) {
            return;
        }

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return;
        }

        if(
            $block->getType() == 'adminhtml/widget_grid_massaction'
            && $block->getRequest()->getControllerName() == 'sales_order'
        ) {
            $block->addItem('keycash_verify_order', array(
                'label' => $helper->__('Verify with KeyCash'),
                'url' => $block->getUrl('*/keycash_order/massverify')
            ));
        }
    }

    public function updateKeycashOrderStatus(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled() || !$helper->isSendOrdersEnabled()) {
            return;
        }

        $order = $observer->getOrder();
        $originalIncrementId = $order->getOriginalIncrementId();

        if (Mage::registry('keycash_order_status_update_request')) {
            return;
        }

        if ($originalIncrementId && !$order->getOrigData()) {
            $keycashOrder = Mage::getModel('keycash_core/order')
                ->loadByIncrementId($originalIncrementId);

            if (!$keycashOrder->getId()) {
                Mage::register(
                    'keycash_order_status_update_request', 
                    -1
                );

                return;
            }

            $keycashOrder
                ->setSalesOrderId($order->getEntityId())
                ->save();

            $apiRequestModel = Mage::getModel('keycash_core/apirequest');
            $apiRequestModel
                ->setRequestName('order_update')
                ->setRequestData($order->getEntityId())
                ->save();

            Mage::register(
                'keycash_order_status_update_request', 
                1
            );

            return;
        }

        if (!$order->getOrigData()) {
            return;
        }

        $orderId = $order->getEntityId();
        $keycashOrder = Mage::getModel('keycash_core/order')
            ->loadBySalesOrderId($orderId);

        if (!$keycashOrder->getId()) {
            return;
        }

        if (
            $order->getOrigData('state') != $order->getState()
            || $order->getOrigData('status') != $order->getStatus()
        ) {
            $apiRequestModel = Mage::getModel('keycash_core/apirequest');
            $apiRequestModel
                ->setRequestName('order_update')
                ->setRequestData($orderId)
                ->save();

            $keycashOrderStatusUpdateRequest = 1;
        } else {
            $keycashOrderStatusUpdateRequest = -1;
        }

        Mage::register(
            'keycash_order_status_update_request', 
            $keycashOrderStatusUpdateRequest
        );
    }

    public function sendCronHeartbeat()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled()) {
            return;
        }

        $notificationModel = Mage::getModel('adminnotification/inbox');

        $helper->setCronHeartbeat(Mage::getModel('core/date')->gmtTimestamp());

        $cronHeartbeatWarningNotification = $helper->getCronHeartbeatWarningNotification();
        $notification = $notificationModel->load($cronHeartbeatWarningNotification);
        if (!$notification->getIsRemove()) {
            $notification->setIsRemove(1)->save();
            $helper->setCronHeartbeatWarningNotification(null);
        }
    }

    public function createKeycashOrders()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled() || !$helper->isSendOrdersEnabled()) {
            return;
        }

        $apiRequestModel = Mage::getModel('keycash_core/apirequest');
        $keycashOrderModel = Mage::getModel('keycash_core/order');

        $data = $keycashOrderModel->getApiOrderCreationData();

        if ($data) {
            $result = $apiRequestModel->executeOrderCreation($data);

            if ($result) {
                $keycashOrderModel->getResource()->insertMultiple($result);
            }
        }
    }

    public function updateKeycashOrdersVerificationStatus()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled()) {
            return;
        }

        $ordersToUpdate = array();
        $apiRequestModel = Mage::getModel('keycash_core/apirequest');
        $verificationStateFilter = array(
            'neq' => Keycash_Core_Model_Source_Order_Verification_State::UNATTEMPTED
        );

        $keycashOrderCollection = Mage::getModel('keycash_core/order')->getCollection()
            ->addFieldToSelect('sales_order_id')
            ->addFieldToSelect('keycash_order_id')
            ->addFieldToSelect('increment_id')
            ->addFieldToSelect('is_verified')
            ->addFieldToFilter('is_verified', 0)
            ->addFieldToFilter('verification_state', $verificationStateFilter);

        $ordersLimit = $helper->getSendOrdersLimit();
        if ($ordersLimit) {
            $keycashOrderCollection->getSelect()->limit($ordersLimit);
        }

        foreach ($keycashOrderCollection as $order) {
            $orderData = $apiRequestModel->executeOrderRetrieveRequest(
                $order->getKeycashOrderId(),
                true
            );

            if (!$orderData) {
                continue;
            }

            $verificationStateFlag = isset($orderData['verification_state'])
                && ($order->getVerificationState() != $orderData['verification_state']);

            if (
                $verificationStateFlag
                || ($order->getisVerified() != $orderData['is_verified'])
            ) {
                $orderData['sales_order_id'] = $order->getSalesOrderId();
                $orderData['increment_id'] = $order->getIncrementId();

                $ordersToUpdate[$order->getKeycashOrderId()] = $orderData;
            }
        }

        if ($ordersToUpdate) {
            Mage::getModel('keycash_core/resource_order')
                ->insertMultiple($ordersToUpdate, true);
        }
    }

    public function runScheduledApiRequests()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isModuleEnabled() || !$helper->isSendOrdersEnabled()) {
            return;
        }

        $apiRequestCollection = Mage::getModel('keycash_core/apirequest')->getCollection();
        foreach ($apiRequestCollection as $apiRequest) {
            $apiRequest->runScheduledRequest();
            $apiRequest->delete();
        }
    }
}
