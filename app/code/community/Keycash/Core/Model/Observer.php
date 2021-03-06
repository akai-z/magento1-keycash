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
class Keycash_Core_Model_Observer
{
    // @codingStandardsIgnoreEnd
    /**
     * Checks the last time a cron job has been run,
     * and adds a notification if it has not been run for a long time.
     */
    public function checkCronHeartbeatStatus()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled()) {
            return;
        }

        $lastHeartbeat = $helper->getLastCronHeartbeat();
        if ($lastHeartbeat) {
            $heatbeatAge = Mage::getModel('core/date')->gmtTimestamp() - $lastHeartbeat;
            if ($heatbeatAge <= $helper->getCronHeartbeatInterval()) {
                return;
            }
        }

        $lastNotificationId = $helper->getCronHeartbeatWarningNotification();
        if ($lastNotificationId) {
            $notificationModel = Mage::getModel('adminnotification/inbox');
            $notification = $notificationModel->load($lastNotificationId);

            if ($notification->getId() && !$notification->getIsRemove()) {
                if ($notification->getIsRead()) {
                    $notification->setIsRead(0)->save();
                }

                return;
            }
        }

        $notificationTitle = $helper->__('KeyCash scheduled tasks seem to be inactive');
        $notificationDescription = $helper->__(
            'Please make sure that Cron is running.'
            . ' If it is running,'
            . ' this message will disappear later when KeyCash scheduled monitoring task is executed again.'
            . ' Or, you could just remove it manually.'
        );

        $notificationModel = Mage::getModel('adminnotification/inbox');
        $notificationModel->addMajor(
            $notificationTitle,
            $notificationDescription,
            Mage::helper('adminhtml')->getUrl('adminhtml/notification/index', array('_secure' => true))
        );

        $notification = $notificationModel->loadLatestNotice();
        $helper->setCronHeartbeatWarningNotification($notification->getNotificationId());
    }

    /**
     * Updates stored server public IP
     */
    public function updatePublicIp()
    {
        $helper = Mage::helper('keycash_core');
        $ip = Mage::helper('core/http')->getServerAddr();

        if ($helper->getPublicIp() != $ip) {
            $helper->setPublicIp($ip);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function removeCronNotificationOnModuleDisable(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        $config = $observer->getObject();

        if ($config->getSection() != 'keycash') {
            return;
        }

        $groups = $config->getGroups();
        $isEnabled = $groups['general']['fields']['enabled']['value'];

        if ($isEnabled) {
            return;
        }

        $lastNotificationId = Mage::helper('keycash_core')
            ->getCronHeartbeatWarningNotification();

        if ($lastNotificationId) {
            $notificationModel = Mage::getModel('adminnotification/inbox');
            $notification = $notificationModel->load($lastNotificationId);

            if ($notification->getId() && !$notification->getIsRemove()) {
                $notification->setIsRemove(1)->save();
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addKeycashVerificationStateToSalesOrderGridCollection(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('keycash_core')->isEnabled()) {
            return;
        }

        $collection = $observer->getEvent()->getOrderGridCollection();
        if (!isset($collection)) {
            return;
        }

        if ($collection instanceof Mage_Sales_Model_Resource_Order_Collection) {
            $keycashOrderTable = Mage::getSingleton('core/resource')
                ->getTableName('keycash_core/keycash_order');

            $column = $collection->getSelect()->getAdapter()->getConcatSql(
                array($keycashOrderTable . '.verification_state', $keycashOrderTable . '.verification_status'),
                '|'
            );

            $canceledOrderStatuses = Mage::getModel(
                'keycash_core/source_order_status_closed'
            )->getUnacceptableStatuses();

            $customCanceledOrderStatuses = Mage::helper('keycash_core')->getCustomCanceledOrderStatuses();
            if ($customCanceledOrderStatuses) {
                $canceledOrderStatuses = array_unique(
                    array_merge(
                        $canceledOrderStatuses,
                        $customCanceledOrderStatuses
                    )
                );
            }

            // TODO move data access related code to a resource model
            // @codingStandardsIgnoreStart
            $collection->getSelect()->joinLeft(
                $keycashOrderTable,
                $collection->getConnection()->quoteInto(
                    'main_table.entity_id = ' . $keycashOrderTable . '.sales_order_id'
                    . ' AND main_table.status NOT IN (?)',
                    $canceledOrderStatuses
                ),
                array('keycash_verification_state' => $column)
            );
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addKeycashVerificationStateColumnToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled()) {
            return;
        }

        $block = $observer->getBlock();
        if (!isset($block)) {
            return;
        }

        if ($block->getType() == 'adminhtml/sales_order_grid') {
            $verificationStateFilterBlock =
                'Keycash_Core_Block_Adminhtml_Sales_Order_Grid_Column_Filter_Verificationstate';

            $keycashVerificationStateColumnData = array(
                'header'   => $helper->__('Verification Status'),
                'renderer' => 'keycash_core/adminhtml_sales_order_grid_column_renderer_verificationstate',
                'align'    => 'center',
                'width'    => '150px',
                'type'     => 'options',
                'index'    => 'keycash_verification_state',
                'options'  => Mage::getModel('keycash_core/source_order_verification_state')->getFlatOptions(),
                'column_css_class' => 'v-middle',
                'filter_condition_callback' => array($verificationStateFilterBlock, 'filterOrderByVerificationState')
            );

            $block->addColumnAfter(
                'keycash_verification_state',
                $keycashVerificationStateColumnData,
                'real_order_id'
            );

            $block->sortColumnsByOrder();
        }

    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addKeycashOrdersMassVerificationToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled()) {
            return;
        }

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return;
        }

        if ($block->getType() == 'adminhtml/widget_grid_massaction'
            && $block->getRequest()->getControllerName() == 'sales_order'
        ) {
            $block->addItem(
                'keycash_verify_order',
                array(
                    'label' => $helper->__('Verify with KeyCash'),
                    'url' => $block->getUrl('*/keycash_order/massverify')
                )
            );
        }
    }

    /**
     * @todo refactor method
     *
     * @param Varien_Event_Observer $observer
     */
    public function updateKeycashOrderStatus(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled() || !$helper->isSendOrdersEnabled()) {
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

        // TODO get rid of duplicate code
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

        if (in_array($order->getOrigData('status'), $closedOrderStatuses)) {
            return;
        }

        $orderId = $order->getEntityId();
        $keycashOrder = Mage::getModel('keycash_core/order')
            ->loadBySalesOrderId($orderId);

        if (!$keycashOrder->getId()) {
            return;
        }

        if ($order->getOrigData('state') != $order->getState()
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

    /**
     * Updates cron heartbeat time and removes cron notification
     */
    public function sendCronHeartbeat()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled()) {
            return;
        }

        $notificationModel = Mage::getModel('adminnotification/inbox');

        $helper->setCronHeartbeat(Mage::getModel('core/date')->gmtTimestamp());

        $cronHeartbeatWarningNotification = $helper->getCronHeartbeatWarningNotification();
        $notification = $notificationModel->load($cronHeartbeatWarningNotification);

        if ($notification->getId() && !$notification->getIsRemove()) {
            $notification->setIsRemove(1)->save();
            $helper->setCronHeartbeatWarningNotification(null);
        }
    }

    public function createKeycashOrders()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled() || !$helper->isSendOrdersEnabled()) {
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

    public function verifyOrders()
    {
        $helper = Mage::helper('keycash_core');

        if (!$helper->isEnabled()
            || !$helper->isSendOrdersEnabled()
            || !$helper->isAutoOrderVerificationEnabled()
        ) {
            return;
        }

        $ordersToUpdate = array();
        $apiRequestModel = Mage::getModel('keycash_core/apirequest');
        $verificationStateFilter = array(
            'eq' => Keycash_Core_Model_Source_Order_Verification_State::NOT_DISPATCHED
        );

        $keycashOrderCollection = Mage::getModel('keycash_core/order')->getCollection()
            ->addFieldToSelect('sales_order_id')
            ->addFieldToSelect('keycash_order_id')
            ->addFieldToSelect('increment_id')
            ->addFieldToFilter('verification_state', $verificationStateFilter);

        $ordersLimit = $helper->getSendOrdersLimit();
        if ($ordersLimit) {
            // TODO move data access related code to a resource model
            // @codingStandardsIgnoreStart
            $keycashOrderCollection->getSelect()->limit($ordersLimit);
            // @codingStandardsIgnoreEnd
        }

        foreach ($keycashOrderCollection as $order) {
            $orderData = $apiRequestModel->executeOrderVerification(
                $order->getKeycashOrderId()
            );

            if (!$orderData
                || (isset($orderData['status']) && !$orderData['status'])
            ) {
                continue;
            }

            $orderData['sales_order_id'] = $order->getSalesOrderId();
            $orderData['increment_id'] = $order->getIncrementId();

            $ordersToUpdate[$order->getKeycashOrderId()] = $orderData;
        }

        if ($ordersToUpdate) {
            Mage::getModel('keycash_core/resource_order')
                ->insertMultiple($ordersToUpdate, true);
        }
    }

    public function updateKeycashOrdersVerificationStatus()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled()) {
            return;
        }

        $ordersToUpdate = array();
        $apiRequestModel = Mage::getModel('keycash_core/apirequest');
        $verificationStateFilter = array(
            'neq' => Keycash_Core_Model_Source_Order_Verification_State::NOT_DISPATCHED
        );

        $keycashOrderCollection = Mage::getModel('keycash_core/order')->getCollection()
            ->addFieldToSelect('sales_order_id')
            ->addFieldToSelect('keycash_order_id')
            ->addFieldToSelect('increment_id')
            ->addFieldToSelect('is_verified')
            ->addFieldToFilter('is_verified', 0)
            ->addFieldToFilter('verification_state', $verificationStateFilter);

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

            if ($verificationStateFlag
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

    /**
     * Executes stored scheduled API request
     */
    public function runScheduledApiRequests()
    {
        $helper = Mage::helper('keycash_core');
        if (!$helper->isEnabled() || !$helper->isSendOrdersEnabled()) {
            return;
        }

        $requestsToDelete = array();
        $apiRequestModel = Mage::getModel('keycash_core/apirequest');

        foreach ($apiRequestModel->getCollection() as $apiRequest) {
            $apiRequest->runScheduledRequest();
            $requestsToDelete[] = $apiRequest->getRequestId();
        }

        if ($requestsToDelete) {
            $apiRequestModel->getResource()->delete($requestsToDelete);
        }
    }
}
