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
class Keycash_Core_Model_Apirequest extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('keycash_core/apirequest');
    }

    /**
     * Executes stored scheduled API request
     */
    public function runScheduledRequest()
    {
        $requestName = ucwords(str_replace('_', ' ', $this->getRequestName()));
        $requestFunction = 'execute' . str_replace(' ', '', $requestName);

        call_user_func(array($this, $requestFunction));
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        $requestData = @unserialize(parent::getRequestData());

        if (false === $requestData && $requestData != 'b:0;') {
            $requestData = parent::getRequestData();
        }

        return $requestData;
    }

    /**
     * Executes KeyCash order creation API request
     *
     * @param array $requestData
     * @return array
     */
    public function executeOrderCreation($requestData = array())
    {
        $apiHelper = Mage::helper('keycash_core/api');
        $requestData = !$requestData ? $this->getRequestData() : $requestData;

        if (empty($requestData)) {
            $apiHelper->log('[API-ERROR]: Could not create order. Order data is not provided.');
            return false;
        }

        if (!is_array($requestData) || isset($requestData['data'])) {
            $result = $apiHelper->sendRequest(
                $apiHelper->getOrderCreateAction(),
                array(),
                $requestData,
                Varien_Http_Client::POST
            );

            $result = $this->getOrderCreationResponseData(
                $result,
                $requestData
            );
        } else {
            $result = array();
            $requestAction = $apiHelper->getOrderCreateAction();

            foreach ($requestData as $orderId => $order) {
                $response = $apiHelper->sendRequest(
                    $requestAction,
                    array(),
                    $order,
                    Varien_Http_Client::POST
                );

                if ($response) {
                    $responseData = $this->getOrderCreationResponseData(
                        $response,
                        $orderId
                    );

                    if ($responseData) {
                        $result[$orderId] = $responseData;
                    }
                }
            }
        }

        return count($result) == 1 ? reset($result) : $result;
    }

    /**
     * Executes KeyCash order verification API request
     *
     * @param array $requestData
     * @return array
     */
    public function executeOrderVerification($requestData = array())
    {
        $apiHelper = Mage::helper('keycash_core/api');
        $requestData = !$requestData ? $this->getRequestData() : $requestData;

        if (empty($requestData)) {
            $apiHelper->log('[API-ERROR]: Could not verify order(s). Orders ID(s) is/are not provided.');
            return false;
        }

        $params = array(
            'url_params' => array(
                'order_id' => ''
            )
        );

        if (!is_array($requestData)) {
            $params['url_params']['order_id'] = $requestData;

            $result = $apiHelper->sendRequest(
                $apiHelper->getOrderVerifyAction(),
                $params,
                array(),
                Varien_Http_Client::POST
            );

            $result = $this->getOrderVerificationResponseData(
                $result,
                $params['url_params']['order_id']
            );
        } else {
            $result = array();
            $requestAction = $apiHelper->getOrderVerifyAction();

            foreach ($requestData as $order) {
                $params['url_params']['order_id'] = $order;

                $response = $apiHelper->sendRequest(
                    $requestAction,
                    $params,
                    array(),
                    Varien_Http_Client::POST
                );

                if ($response) {
                    $orderId = $params['url_params']['order_id'];

                    $responseData = $this->getOrderVerificationResponseData(
                        $response,
                        $orderId
                    );

                    if ($responseData) {
                        $result[$orderId] = $responseData;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Executes KeyCash mass order verification API request
     *
     * @param array $requestData
     * @return array
     */
    public function executeMassOrderVerification($requestData = array())
    {
        $apiHelper = Mage::helper('keycash_core/api');
        $requestData = !$requestData ? $this->getRequestData() : $requestData;

        if (empty($requestData)) {
            $apiHelper->log('[API-ERROR]: Could not mass verify orders. Request data is empty.');
            return false;
        }

        $orderIds = $requestData;

        $keycashOrderModel = Mage::getModel('keycash_core/order');
        $keycashOrderCollection = $keycashOrderModel->getCollection()
            ->addFieldToSelect('keycash_order_id')
            ->addFieldToSelect('sales_order_id')
            ->addFieldToSelect('increment_id')
            ->addFieldToSelect('is_verified')
            ->addFieldToFilter('sales_order_id', array('in' => $orderIds));

        $verificationLimit = Mage::helper('keycash_core')->getSendOrdersLimit();
        if ($verificationLimit) {
            $keycashOrderCollection->getSelect()->limit($verificationLimit);
        }

        $categorizedKeycashOrders = array();
        $keycashOrders = array();
        foreach ($keycashOrderCollection as $keycashOrder) {
            $verificationStatus = $keycashOrder->getIsVerified()
                ? 'verified' : 'unverified';

            $categorizedKeycashOrders[$verificationStatus][$keycashOrder->getSalesOrderId()] = array(
                'keycash_order_id' => $keycashOrder->getKeycashOrderId(),
                'increment_id' => $keycashOrder->getIncrementId()
            );
        }

        $unsubmittedOrders = $orderIds;
        if (!empty($categorizedKeycashOrders['verified'])) {
            $unsubmittedOrders = array_diff(
                $unsubmittedOrders,
                array_keys($categorizedKeycashOrders['verified'])
            );
        }
        if (!empty($categorizedKeycashOrders['unverified'])) {
            $unsubmittedOrders = array_diff(
                $unsubmittedOrders,
                array_keys($categorizedKeycashOrders['unverified'])
            );

            $keycashOrders = $categorizedKeycashOrders['unverified'];
        }

        if ($unsubmittedOrders) {
            $dbInsertData = array();
            $data = $keycashOrderModel->getApiOrderCreationData($unsubmittedOrders);

            $result = $this->executeOrderCreation($data);

            foreach ($result as $order) {
                if ($order) {
                    $dbInsertData[] = $order;
                    $keycashOrders[$order['sales_order_id']] = array(
                        'keycash_order_id' => $order['keycash_order_id'],
                        'increment_id' => $order['increment_id']
                    );
                }
            }

            if ($dbInsertData) {
                $keycashOrderModel->getResource()->insertMultiple($dbInsertData);
            }
        }

        if (!$keycashOrders) {
            return;
        }

        $keycashOrderIds = array_map(
            function ($keycashOrder) {
                return $keycashOrder['keycash_order_id'];
            },
            $keycashOrders
        );

        $verificationResponse = $this->executeOrderVerification($keycashOrderIds);

        if (!$verificationResponse) {
            return;
        }

        $ordersToUpdate = array();
        foreach ($verificationResponse as $orderId => $order) {
            if (isset($order['status']) && !$order['status']) {
                continue;
            }

            $ordersToUpdate[$orderId] = $order;
            foreach ($keycashOrders as $keycashSalesOrderId => $keycashOrder) {
                if ($orderId == $keycashOrder['keycash_order_id']) {
                    $ordersToUpdate[$orderId]['sales_order_id'] = $keycashSalesOrderId;
                    $ordersToUpdate[$orderId]['increment_id'] = $keycashOrder['increment_id'];

                    unset($keycashOrders[$keycashSalesOrderId]);

                    break;
                }
            }
        }

        if ($ordersToUpdate) {
            Mage::getModel('keycash_core/resource_order')
                ->insertMultiple($ordersToUpdate, true);
        }
    }

    /**
     * Executes KeyCash order data retrieve API request
     *
     * @param array $requestData
     * @param bool $isIncludeVerifications
     * @return array
     */
    public function executeOrderRetrieveRequest($orderId, $isIncludeVerifications = false)
    {
        $apiHelper = Mage::helper('keycash_core/api');

        if (empty($orderId)) {
            $apiHelper->log('[API-ERROR]: Could not retrieve order. Order ID is not provided.');
            return false;
        }

        $params = array(
            'url_params' => array(
                'order_id' => $orderId
            )
        );

        $result = $apiHelper->sendRequest(
            $apiHelper->getOrderRetrieveAction($isIncludeVerifications),
            $params,
            array(),
            Varien_Http_Client::GET
        );

        if ($isIncludeVerifications && $result) {
            $result = $this->getOrderRetrieveRequestVerificationResponseData($result);
        }

        return $result;
    }

    /**
     * Executes KeyCash order data update API request
     *
     * @param array $requestData
     * @return array
     */
    public function executeOrderUpdate($requestData = array())
    {
        $apiHelper = Mage::helper('keycash_core/api');
        $requestData = !$requestData ? $this->getRequestData() : $requestData;

        if (empty($requestData)) {
            $apiHelper->log('[API-ERROR]: Could not edit order status. Order ID is not provided.');
            return false;
        }

        $data = Mage::getModel('keycash_core/order')
            ->getApiOrderCreationData($requestData, true);

        $result = array();
        $requestAction = $apiHelper->getOrderUpdateAction();

        foreach ($data as $orderId => $order) {
            $params = array(
                'url_params' => array(
                    'order_id' => $orderId
                )
            );

            $response = $apiHelper->sendRequest(
                $requestAction,
                $params,
                $order,
                Varien_Http_Client::PUT
            );

            if ($response) {
                $result[$orderId] = $response;
            }
        }

        return $result;
    }

    /**
     * Retrieves processed KeyCash order creation response data
     *
     * @param array $response
     * @param int|null $orderId
     * @return array
     */
    public function getOrderCreationResponseData($response, $orderId = null)
    {
        $data = array();

        if (
            (isset($response['status']) && !$response['status'])
            || !isset($response['response_body']['data'][0])
        ) {
            return $data;
        }

        $orderData = $response['response_body']['data'][0];

        if ($orderId) {
            $data['sales_order_id'] = $orderId;
        }

        $data['keycash_order_id'] = $orderData['id'];
        $data['increment_id'] = $orderData['attributes']['external_order_reference'];
        $data['is_verified'] = $orderData['attributes']['verified_state'] == 'true' ? 1 : 0;

        return $data;
    }

    /**
     * Retrieves processed KeyCash order verification response data
     *
     * @param array $response
     * @param int|null $orderId
     * @return array
     */
    public function getOrderVerificationResponseData($response, $orderId = null)
    {
        $data = array();

        if (isset($response['status']) && !$response['status']) {
            $alreadyVerifiedOrderResponseCode = Mage::helper('keycash_core/api')
                ->getAlreadyVerifiedOrderResponseCode();

            if ($response['code'] == $alreadyVerifiedOrderResponseCode) {
                $data = array(
                    'status' => $response['status'],
                    'code' => $response['code']
                );
            }

            return $data;
        }

        if (!isset($response['response_body']['data'][0])) {
            return $data;
        }

        $verificationData = $response['response_body']['data'][0]['attributes'];

        if ($orderId) {
            $data['keycash_order_id'] = $orderId;
        }

        $data['verification_state'] = $verificationData['state'];
        $data['verification_status'] = $verificationData['status'];
        $data['verification_strategy'] = $verificationData['strategy'];

        return $data;
    }

    /**
     * Retrieves KeyCash order verification data from order retrieve request response data
     *
     * @param array $response
     * @return array
     */
    public function getOrderRetrieveRequestVerificationResponseData($response)
    {
        $data = array();

        if (
            (isset($response['status']) && !$response['status'])
            || !isset($response['response_body']['data'][0])
        ) {
            return $data;
        }

        $orderData = $response['response_body']['data'][0];

        $data = array(
            'keycash_order_id' => $orderData['id'],
            'is_verified' => $orderData['attributes']['verified_state'] == 'true' ? 1 : 0
        );

        if (isset($response['response_body']['included'])) {
            foreach ($response['response_body']['included'] as $includedData) {
                if ($includedData['type'] == 'verification') {
                    $includedDataAttributes = $includedData['attributes'];

                    $data['verification_state'] = $includedDataAttributes['state'];
                    $data['verification_status'] = $includedDataAttributes['status'];
                    $data['verification_strategy'] = $includedDataAttributes['strategy'];

                    if ($data['is_verified']) {
                        $data['verification_date'] = $includedDataAttributes['updated_at'];
                    }

                    break;
                }
            }
        }

        return $data;
    }
}
