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
class Keycash_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Config general XML paths
     */
    const XML_PATH_GENERAL_ENABLED = 'keycash/general/enabled';
    const XML_PATH_GENERAL_ACCOUNT_ID = 'keycash/general/account_id';
    const XML_PATH_GENERAL_STORE_ID = 'keycash/general/store_id';
    const XML_PATH_GENERAL_API_KEY = 'keycash/general/api_key';
    const XML_PATH_GENERAL_PUBLIC_IP = 'keycash/general/public_ip';

    /**
     * Config KeyVerify XML paths
     */
    const XML_PATH_KEY_VERIFY_SEND_ORDERS = 'keycash/key_verify/send_orders';
    const XML_PATH_KEY_VERIFY_SEND_ORDERS_LIMIT = 'keycash/key_verify/send_orders_limit';
    const XML_PATH_KEY_VERIFY_ORDER_PAYMENT_FILTER = 'keycash/key_verify/allow_order_payment_filter';
    const XML_PATH_KEY_VERIFY_ORDER_FILTER_PAYMENT_METHODS = 'keycash/key_verify/order_filter_payment_methods';
    const XML_PATH_KEY_VERIFY_ORDER_SHIPPING_COUNTRY_FILTER = 'keycash/key_verify/allow_order_shipping_country_filter';
    const XML_PATH_KEY_VERIFY_ORDER_FILTER_SHIPPING_COUNTRIES = 'keycash/key_verify/order_filter_shipping_countries';
    const XML_PATH_KEY_VERIFY_CUSTOM_CANCELED_ORDER_STATUS = 'keycash/key_verify/custom_canceled_order_status';

    /**
     * Config cron heartbeat XML paths
     */
    const XML_PATH_CRON_HEARTBEAT = 'keycash/cron_heartbeat/tick';
    const XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION = 'keycash/cron_heartbeat/warning_notification';

    /**
     * Cron heartbeat interval
     */
    const CRON_HEARTBEAT_INTERVAL = 86400; // 24 hours

    /**
     * KeyCash default log file name
     */
    const DEFAULT_LOG_FILE = 'keycash.log';

    /**
     * Checks whether the module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_ENABLED);
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_ACCOUNT_ID));
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_STORE_ID));
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_API_KEY));
    }

    /**
     * Retrieves the server's stored public IP
     *
     * @return string
     */
    public function getPublicIp()
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PUBLIC_IP);
    }

    /**
     * Stores the server's public IP
     *
     * @param string $ip
     */
    public function setPublicIp($ip)
    {
        Mage::getConfig()->saveConfig(self::XML_PATH_GENERAL_PUBLIC_IP, $ip);
    }

    /**
     * Checks whether sending orders details to KeyCash is enabled
     *
     * @return bool
     */
    public function isSendOrdersEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_SEND_ORDERS);
    }

    /**
     * Retrieves the limit for sending orders to KeyCash
     *
     * @return int
     */
    public function getSendOrdersLimit()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_SEND_ORDERS_LIMIT);
    }

    /**
     * Checks whether order sending payment methods filter is enabled
     *
     * @return bool
     */
    public function isOrderPaymentFilterEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_PAYMENT_FILTER);
    }

    /**
     * Retrieves order sending payment methods filter methods
     *
     * @return array
     */
    public function getOrderFilterPaymentMethods()
    {
        $result = array();
        if ($this->isOrderPaymentFilterEnabled()) {
            $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_FILTER_PAYMENT_METHODS);
            $result = array_map('trim', explode(',', $result));
        }

        return $result;
    }

    /**
     * Checks whether order sending shipping countries filter is enabled
     *
     * @return bool
     */
    public function isOrderShippingCountryFilterEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_SHIPPING_COUNTRY_FILTER);
    }

    /**
     * Retrieves order sending shipping countries filter countries
     *
     * @return array
     */
    public function getOrderFilterShippingCountries()
    {
        $result = array();
        if ($this->isOrderShippingCountryFilterEnabled()) {
            $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_FILTER_SHIPPING_COUNTRIES);
            $result = array_map('trim', explode(',', $result));
        }

        return $result;
    }

    /**
     * Retrieves order sending custom canceled orders filter statuses
     *
     * @return array
     */
    public function getCustomCanceledOrderStatuses()
    {
        $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_CUSTOM_CANCELED_ORDER_STATUS);
        $result = array_map('trim', explode(',', $result));

        return $result;
    }

    /**
     * Retrieves last successful cron heartbeat
     *
     * @return int
     */
    public function getLastCronHeartbeat()
    {
        return Mage::getStoreConfig(self::XML_PATH_CRON_HEARTBEAT);
    }

    /**
     * Stores successful heartbeat time
     *
     * @param int $heartbeat
     */
    public function setCronHeartbeat($heartbeat)
    {
        Mage::getConfig()->saveConfig(self::XML_PATH_CRON_HEARTBEAT, $heartbeat);
    }

    /**
     * @return int
     */
    public function getCronHeartbeatInterval()
    {
        return self::CRON_HEARTBEAT_INTERVAL;
    }

    /**
     * Retrieves current cron heartbeat warning notification ID
     *
     * @return int
     */
    public function getCronHeartbeatWarningNotification()
    {
        return Mage::getStoreConfig(self::XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION);
    }

    /**
     * Stores new cron heartbeat warning notification ID
     *
     * @param int $notificationId
     */
    public function setCronHeartbeatWarningNotification($notificationId)
    {
        Mage::getConfig()->saveConfig(
            self::XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION,
            $notificationId
        );
    }

    /**
     * Logs KeyCash related data and messages
     *
     * @param mixed $data
     * @param string $logFile
     */
    public function log($data, $logFile = self::DEFAULT_LOG_FILE)
    {
        if ($data instanceof Exception) {
            Mage::logException($data);
        } else {
            Mage::log($data, null, $logFile);
        }
    }
}
