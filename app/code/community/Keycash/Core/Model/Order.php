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
class Keycash_Core_Model_Order extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('keycash_core/order');
    }

    /**
     * @param int $orderId
     * @return Keycash_Core_Model_Order
     */
    public function loadBySalesOrderId($orderId)
    {
        $this->setData($this->getResource()->loadBySalesOrderId($orderId));
        return $this;
    }

    /**
     * @param int $orderId
     * @return Keycash_Core_Model_Order
     */
    public function loadByKeycashOrderId($orderId)
    {
        $this->setData($this->getResource()->loadByKeycashOrderId($orderId));
        return $this;
    }

    /**
     * @param int $incrementId
     * @return Keycash_Core_Model_Order
     */
    public function loadByIncrementId($incrementId)
    {
        $this->setData($this->getResource()->loadByIncrementId($incrementId));
        return $this;
    }

    /**
     * @param int|Mage_Sales_Model_Order $order
     * @param bool $checkKeycashOrder
     * @return bool
     */
    public function isOrderVerifiable($order, $checkKeycashOrder = true)
    {
        $helper = Mage::helper('keycash_core');

        if (is_object($order)) {
            $orderId = $order->getEntityId();
        } else {
            $orderId = $order;
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('entity_id', $orderId)
                ->addAttributeToFilter('relation_child_id', array('null' => true));

            if (!$orderCollection->getSize()) {
                return false;
            }
        }

        $canceledOrderStatuses = Mage::getModel(
            'keycash_core/source_order_status_closed'
        )->getUnacceptableStatuses();

        $customCanceledOrderStatuses = $helper->getCustomCanceledOrderStatuses();
        if ($customCanceledOrderStatuses) {
            $canceledOrderStatuses = array_unique(
                array_merge(
                    $canceledOrderStatuses,
                    $customCanceledOrderStatuses
                )
            );
        }

        if (is_integer($order)) {
            $order = $orderCollection->getFirstItem();
        }
        if (in_array($order->getStatus(), $canceledOrderStatuses)) {
            return false;
        }

        if ($checkKeycashOrder) {
            $keycashOrder = clone $this;
            $keycashOrder = $keycashOrder->loadBySalesOrder($orderId);

            if ($keycashOrder->getId()) {
                return true;
            }
        }

        $allowedPaymentMethods = $helper->getOrderFilterPaymentMethods();
        $allowedShippingCountries = $helper->getOrderFilterShippingCountries();

        if (!$allowedPaymentMethods && !$allowedShippingCountries) {
            return true;
        }

        if (!isset($orderCollection)) {
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('entity_id', $orderId)
                ->addAttributeToFilter('relation_child_id', array('null' => true));
        }

        $orderCollection->getSelect()->reset(Zend_Db_Select::COLUMNS);

        if ($allowedPaymentMethods) {
            $orderCollection->getSelect()
                ->join(
                    array('order_payment' => $orderCollection->getTable('sales/order_payment')),
                    'main_table.entity_id = order_payment.parent_id'
                )
                ->where('order_payment.method IN (?)', $allowedPaymentMethods);
        }
        if ($allowedShippingCountries) {
            $orderCollection->getSelect()
                ->join(
                    array('order_shipping_address' => $orderCollection->getTable('sales/order_address')),
                    'main_table.entity_id = order_shipping_address.parent_id'
                )
                ->where('order_shipping_address.country_id IN (?)', $allowedShippingCountries);
        }

        return $orderCollection->getSize() ? true : false;
    }

    /**
     * Returns a filtered sales order collection
     *
     * @param array $orderIds
     * @param bool $isUpdate
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getSalesOrderCollection($orderIds = array(), $isUpdate = false)
    {
        $helper = Mage::helper('keycash_core');
        $resource = Mage::getSingleton('core/resource');
        $orderCollectionAttributes = $this->getOrderCollectionAttributes();
        $orderCollection = Mage::getModel('sales/order')->getCollection();

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

        foreach ($orderCollectionAttributes['sales_order'] as $salesOrderAttribute) {
            $orderCollection->addAttributeToSelect($salesOrderAttribute);
        }

        $orderCollection->addAttributeToFilter('relation_child_id', array('null' => true));

        $orderCollection->getSelect()
            ->join(
                array('order_billing_address' => $resource->getTableName('sales/order_address')),
                'main_table.entity_id = order_billing_address.parent_id'
                . ' AND main_table.billing_address_id = order_billing_address.entity_id',
                $orderCollectionAttributes['order_billing_address']
            )
            ->join(
                array('order_shipping_address' => $resource->getTableName('sales/order_address')),
                'main_table.entity_id = order_shipping_address.parent_id'
                . ' AND main_table.shipping_address_id = order_shipping_address.entity_id',
                $orderCollectionAttributes['order_shipping_address']
            )
            ->join(
                array('order_payment' => $resource->getTableName('sales/order_payment')),
                'main_table.entity_id = order_payment.parent_id',
                $orderCollectionAttributes['order_payment']
            );

        if ($isUpdate) {
            $orderCollection->getSelect()->join(
                array('keycash_order' => $resource->getTableName('keycash_core/keycash_order')),
                'main_table.entity_id = keycash_order.sales_order_id',
                $orderCollectionAttributes['keycash_order']
            );
        } else {
            $orderCollection->getSelect()
                ->joinLeft(
                    array('keycash_order' => $resource->getTableName('keycash_core/keycash_order')),
                    'main_table.entity_id = keycash_order.sales_order_id',
                    $orderCollectionAttributes['keycash_order']
                )
                ->where('keycash_order.sales_order_id IS NULL')
                ->where('main_table.status NOT IN (?)', $closedOrderStatuses);
        }

        if ($orderIds) {
            $orderCollection->getSelect()->where('main_table.entity_id IN (?)', (array) $orderIds);
        }

        $paymentMethodsFilter = $helper->getOrderFilterPaymentMethods();
        if ($paymentMethodsFilter) {
            $orderCollection->getSelect()->where('order_payment.method IN (?)', $paymentMethodsFilter);
        }

        $shippingCountriesFilter = $helper->getOrderFilterShippingCountries();
        if ($shippingCountriesFilter) {
            $orderCollection->getSelect()->where('order_shipping_address.country_id IN (?)', $shippingCountriesFilter);
        }

        $ordersLimit = $helper->getSendOrdersLimit();
        if ($ordersLimit) {
            $orderCollection->getSelect()->limit($ordersLimit);

            $orderFullCollection = Mage::getModel('sales/order_item')->getCollection();

            $orderFullCollection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns($orderCollectionAttributes['order_item']);

            $orderFullCollection->getSelect()->join(
                array('sales_order' => $orderCollection->getSelect()),
                'main_table.order_id = sales_order.entity_id'
            );

            $orderCollection = $orderFullCollection;
        } else {
            $ordersCollection->getSelect()->join(
                array('order_item' => $resource->getTableName('sales/order_item')),
                'main_table.entity_id = order_item.order_id',
                $orderCollectionAttributes['order_item']
            );
        }

        return $orderCollection;
    }

    /**
     * Retrieves a set of attributes for sales order collection
     *
     * @return array
     */
    public function getOrderCollectionAttributes()
    {
        $exportFields = array(
            'sales_order' => array(
                'order_id' => 'entity_id',
                'increment_id' => 'increment_id',
                'original_increment_id' => 'original_increment_id',
                'store_id' => 'store_id',
                'currency' => 'order_currency_code',
                'order_state' => 'state',
                'order_status' => 'status',
                'items_total' => 'total_qty_ordered',
                'shipping_total' => 'shipping_amount',
                'tax_total' => 'tax_amount',
                'discount_total' => 'discount_amount',
                'grand_total' => 'grand_total',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at'
            ),
            'order_billing_address' => array(
                'order_billing_firstname' => 'firstname',
                'order_billing_lastname' => 'lastname',
                'order_billing_email' => 'email',
                'order_billing_phone' => 'telephone',
                'order_billing_address' => 'street',
                'order_billing_postal_code' => 'postcode',
                'order_billing_country' => 'country_id',
            ),
            'order_shipping_address' => array(
                'order_shipping_firstname' => 'firstname',
                'order_shipping_lastname' => 'lastname',
                'order_shipping_email' => 'email',
                'order_shipping_phone' => 'telephone',
                'order_shipping_address' => 'street',
                'order_shipping_postal_code' => 'postcode',
                'order_shipping_country' => 'country_id'
            ),
            'order_payment' => array(
                'payment_method' => 'method'
            ),
            'order_item' => array(
                'order_item_name' => 'name',
                'sku' => 'sku',
                'order_item_qty_ordered' => 'qty_ordered',
                'order_item_row_total' => 'row_total',
                'order_item_tax_total' => 'tax_amount',
                'order_item_discount_total' => 'discount_amount',
                'order_item_row_total_incl_tax' => 'row_total_incl_tax'
            ),
            'keycash_order' => array(
                'keycash_order_id' => 'keycash_order_id',
                'keycash_increment_id' => 'increment_id',
                'verification_status' => 'verification_status'
            )
        );

        return $exportFields;
    }

    /**
     * Retrieves API order creation request data
     *
     * @param int|null $orderId
     * @param bool $isUpdate
     * @param bool $asJson
     * @return array|string
     */
    public function getApiOrderCreationData($orderId = null, $isUpdate = false, $asJson = false)
    {
        $data = array();
        $orderCollection = $this->getSalesOrderCollection($orderId, $isUpdate);

        if (!$orderCollection->getSize()) {
            return $data;
        }

        $helper = Mage::helper('keycash_core');
        $accountId = $helper->getAccountId();
        $storeId = $helper->getStoreId();
        $ipAddress = Mage::helper('core/http')->getServerAddr();

        if (!$ipAddress) {
            $ipAddress = $helper->getPublicIp();
            if (!$ipAddress) {
                $ipAddress = '127.0.0.1';
            }
        }

        foreach ($orderCollection as $order) {
            $orderId = $isUpdate ? $order->getKeycashOrderId() : $order->getEntityId();

            if ($order->getKeycashIncrementId()) {
                $incrementId = $order->getKeycashIncrementId();
            } else {
                $incrementId = $order->getOriginalIncrementId()
                    ? $order->getOriginalIncrementId()
                    : $order->getIncrementId();
            }

            if (isset($data[$orderId])) {
                $data[$orderId]['data']['relationships']['order_item']['data'][] =
                    $this->getApiOrderCreationOrderItemsData($order);

                continue;
            }

            $billingAddressData = $this->getApiOrderCreationBillingAddressData($order);
            $shippingAddressData = $this->getApiOrderCreationShippingAddressData($order);

            $data[$orderId] = array(
                'data' => array(
                    'type' => 'order',
                    'attributes' => array(
                        'store_id' => $storeId,
                        'account_id' => $accountId,
                        'external_system' => 'magento',
                        'external_order_reference' => $incrementId,
                        'ip_address' => $ipAddress,
                        'user_agent' => '',
                        'currency' => $order->getOrderCurrencyCode(),
                        'language' => $this->getOrderLanguage($order),
                        'order_state' => $order->getState(),
                        'order_status' => $order->getStatus(),
                        'verified_state' => 'false',
                        'billing_contact' => $billingAddressData['billing_contact'],
                        'billing_address' => $billingAddressData['billing_address'],
                        'shipping_contact' => $shippingAddressData['shipping_contact'],
                        'shipping_address' => $shippingAddressData['shipping_address'],
                        'totals' => $this->getApiOrderCreationTotalsData($order),
                        'placed_at' => $order->getCreatedAt(),
                        'updated_at' => $order->getUpdatedAt(),
                        'created_at' => $order->getCreatedAt()
                     ),
                    'relationships' => array(
                        'order_item' => array(
                            'data' => array($this->getApiOrderCreationOrderItemsData($order))
                        )
                    )
                )
            );
        }

        if ($asJson) {
            $data = json_encode($data);
        }

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getApiOrderCreationBillingAddressData($order)
    {
        $orderBillingAddressContactName = $order->getOrderBillingFirstname()
            . ' ' . $order->getOrderBillingLastname();
        $country = Mage::getModel('directory/country')->loadByCode($order->getOrderBillingCountry());
        $countryCode = $country->getIso3Code();

        $data = array(
            'billing_contact' => array(
                'name' => $orderBillingAddressContactName,
                'email' => $order->getOrderBillingEmail(),
                'telephone' => $order->getOrderBillingPhone()
            ),
            'billing_address' => array(
                'address_line_1' => $order->getOrderBillingAddress(),
                'address_line_2' => '',
                'address_line_3' => '',
                'address_line_4' => '',
                'address_line_5' => '',
                'postal_code' => $order->getOrderBillingPostalCode(),
                'country_code' => $countryCode,
            )
        );

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getApiOrderCreationShippingAddressData($order)
    {
        $orderShippingAddressContactName = $order->getOrderShippingFirstname()
            . ' ' . $order->getOrderShippingLastname();
        $country = Mage::getModel('directory/country')->loadByCode($order->getOrderShippingCountry());
        $countryCode = $country->getIso3Code();

        $data = array(
            'shipping_contact' => array(
                'name' => $orderShippingAddressContactName,
                'email' => $order->getOrderShippingEmail(),
                'telephone' => $order->getOrderShippingPhone()
            ),
            'shipping_address' => array(
                'address_line_1' => $order->getOrderShippingAddress(),
                'address_line_2' => '',
                'address_line_3' => '',
                'address_line_4' => '',
                'address_line_5' => '',
                'postal_code' => $order->getOrderShippingPostalCode(),
                'country_code' => $countryCode,
            )
        );

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getApiOrderCreationTotalsData($order)
    {
        $data = array(
            'items_total' => $this->getPriceIntValue($order->getTotalQtyOrdered()),
            'shipping_total' => $this->getPriceIntValue($order->getShippingAmount()),
            'tax_total' => $this->getPriceIntValue($order->getTaxAmount()),
            'discount_total' => $this->getPriceIntValue($order->getDiscountAmount()),
            'grand_total' => $this->getPriceIntValue($order->getGrandTotal()),
        );

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getApiOrderCreationOrderItemsData($order)
    {
        $data = array(
            'type' => 'order_item',
            'attributes' => array(
                'name' => $order->getOrderItemName(),
                'sku' => $order->getSku(),
                'quantity' => (int) $order->getOrderItemQtyOrdered(),
                'row_totals' => array(
                    'row_items_total' => $this->getPriceIntValue($order->getOrderItemTaxTotal()),
                    'row_tax_total' => $this->getPriceIntValue($order->getOrderItemRowTotal()),
                    'row_discount_total' => $this->getPriceIntValue($order->getOrderItemDiscountTotal()),
                    'row_total' => $this->getPriceIntValue($order->getOrderItemRowTotalInclTax())
                )
            )
        );

        return $data;
    }

    /**
     * @param float|string|int $price
     * @return string
     */
    protected function getPriceIntValue($price)
    {
        return !empty($price) && (int) $price != 0 ? str_replace('.', '', abs($price)) : 0;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function getOrderLanguage($order)
    {
        $localeCode = Mage::getStoreConfig('general/locale/code', $order->getStoreId());
        return strtoupper(substr($localeCode, 0, strpos($localeCode, '_')));
    }
}
