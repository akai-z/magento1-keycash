<?php

class Keycash_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_GENERAL_ENABLED = 'keycash/general/enabled';
    const XML_PATH_GENERAL_ACCOUNT_ID = 'keycash/general/account_id';
    const XML_PATH_GENERAL_STORE_ID = 'keycash/general/store_id';
    const XML_PATH_GENERAL_API_KEY = 'keycash/general/api_key';
    const XML_PATH_GENERAL_PUBLIC_IP = 'keycash/general/public_ip';

    const XML_PATH_KEY_VERIFY_SEND_ORDERS = 'keycash/key_verify/send_orders';
    const XML_PATH_KEY_VERIFY_SEND_ORDERS_LIMIT = 'keycash/key_verify/send_orders_limit';
    const XML_PATH_KEY_VERIFY_ORDER_PAYMENT_FILTER = 'keycash/key_verify/allow_order_payment_filter';
    const XML_PATH_KEY_VERIFY_ORDER_FILTER_PAYMENT_METHODS = 'keycash/key_verify/order_filter_payment_methods';
    const XML_PATH_KEY_VERIFY_ORDER_SHIPPING_COUNTRY_FILTER = 'keycash/key_verify/allow_order_shipping_country_filter';
    const XML_PATH_KEY_VERIFY_ORDER_FILTER_SHIPPING_COUNTRIES = 'keycash/key_verify/order_filter_shipping_countries';
    const XML_PATH_KEY_VERIFY_CUSTOM_CANCELED_ORDER_STATUS = 'keycash/key_verify/custom_canceled_order_status';

    const XML_PATH_CRON_HEARTBEAT = 'keycash/cron_heartbeat/tick';
    const XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION = 'keycash/cron_heartbeat/warning_notification';

    const CRON_HEARTBEAT_INTERVAL = 86400; // 24 hours

    const DEFAULT_LOG_FILE = 'keycash.log';

    public function isModuleEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_ENABLED);
    }

    public function getAccountId()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_ACCOUNT_ID));
    }

    public function getStoreId()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_STORE_ID));
    }

    public function getApiKey()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_GENERAL_API_KEY));
    }

    public function getPublicIp()
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PUBLIC_IP);
    }

    public function setPublicIp($ip)
    {
        Mage::getConfig()->saveConfig(self::XML_PATH_GENERAL_PUBLIC_IP, $ip);
    }

    public function isSendOrdersEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_SEND_ORDERS);
    }

    public function getSendOrdersLimit()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_SEND_ORDERS_LIMIT);
    }

    public function isOrderPaymentFilterEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_PAYMENT_FILTER);
    }

    public function getOrderFilterPaymentMethods()
    {
        $result = array();
        if ($this->isOrderPaymentFilterEnabled()) {
            $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_FILTER_PAYMENT_METHODS);
            $result = array_map('trim', explode(',', $result));
        }

        return $result;
    }

    public function isOrderShippingCountryFilterEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_SHIPPING_COUNTRY_FILTER);
    }

    public function getOrderFilterShippingCountries()
    {
        $result = array();
        if ($this->isOrderShippingCountryFilterEnabled()) {
            $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_ORDER_FILTER_SHIPPING_COUNTRIES);
            $result = array_map('trim', explode(',', $result));
        }

        return $result;
    }

    public function getCustomCanceledOrderStatuses()
    {
        $result = Mage::getStoreConfig(self::XML_PATH_KEY_VERIFY_CUSTOM_CANCELED_ORDER_STATUS);
        $result = array_map('trim', explode(',', $result));

        return $result;
    }

    public function getLastCronHeartbeat()
    {
        return Mage::getStoreConfig(self::XML_PATH_CRON_HEARTBEAT);
    }

    public function setCronHeartbeat($heartbeat)
    {
        Mage::getConfig()->saveConfig(self::XML_PATH_CRON_HEARTBEAT, $heartbeat);
    }

    public function getCronHeartbeatInterval()
    {
        return self::CRON_HEARTBEAT_INTERVAL;
    }

    public function getCronHeartbeatWarningNotification()
    {
        return Mage::getStoreConfig(self::XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION);
    }

    public function setCronHeartbeatWarningNotification($notificationId)
    {
        Mage::getConfig()->saveConfig(
            self::XML_PATH_CRON_HEARTBEAT_WARNING_NOTIFICATION,
            $notificationId
        );
    }

    public function log($data, $logFile = self::DEFAULT_LOG_FILE)
    {
        if ($data instanceof Exception) {
            Mage::logException($data);
        } else {
            Mage::log($data, null, $logFile);
        }
    }
}
