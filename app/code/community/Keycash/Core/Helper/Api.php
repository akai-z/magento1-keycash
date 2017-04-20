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
 * KeyCash API helper
 *
 * @category    Keycash
 * @package     Keycash_Core
 */
// @codingStandardsIgnoreStart
class Keycash_Core_Helper_Api extends Mage_Core_Helper_Abstract
{
    // @codingStandardsIgnoreEnd
    /**
     * API base URL
     */
    const BASE_URL = 'https://api.keycash.co/api/v1/';

    /**
     * Authorization header field
     */
    const AUTHORIZATION_HEADER_FIELD = 'authorization';

    /**
     * Default header fields values
     */
    const DEFAULT_HEADER_CONTENT_TYPE = 'application/json';
    const DEFAULT_HEADER_ACCEPT = 'application/json';

    /**
     * Request data enc type value
     */
    const REQUEST_DATA_ENC_TYPE = 'application/json';

    /**
     * API actions/endpoints
     */
    const ORDER_CREATE_ACTION = 'orders';
    const ORDER_RETRIEVE_ACTION = 'orders/%s';
    const ORDER_UPDATE_ACTION = 'orders/%s';
    const ORDER_VERIFY_ACTION = 'orders/%s/verify';

    /**
     * Order retrieve action verification parameter
     */
    const ORDER_RETRIEVE_ACTION_VERIFICATION_PARAM = '?include=verifications';

    /**
     * Order verification default type
     */
    const ORDER_VERIFICATION_DEFAULT_TYPE = 'bestway';

    /**
     * Already verified order response code
     */
    const ALREADY_VERIFIED_ORDER_RESPONSE_CODE = 422;

    /**
     * API log file name
     */
    const API_LOG_FILE = 'keycash_api.log';

    /**
     * @return string
     */
    public function getOrderCreateAction()
    {
        return self::ORDER_CREATE_ACTION;
    }

    /**
     * @param bool $isIncludeVerifications
     * @return string
     */
    public function getOrderRetrieveAction($isIncludeVerifications = false)
    {
        $includeVerificationsParam = $isIncludeVerifications
            ? self::ORDER_RETRIEVE_ACTION_VERIFICATION_PARAM
            : '';

        return self::ORDER_RETRIEVE_ACTION . $includeVerificationsParam;
    }

    /**
     * @return string
     */
    public function getOrderUpdateAction()
    {
        return self::ORDER_UPDATE_ACTION;
    }

    /**
     * @return string
     */
    public function getOrderVerifyAction()
    {
        return self::ORDER_VERIFY_ACTION;
    }

    /**
     * @return string
     */
    public function getOrderVerificationDefaultType()
    {
        return self::ORDER_VERIFICATION_DEFAULT_TYPE;
    }

    /**
     * @return string
     */
    public function getAlreadyVerifiedOrderResponseCode()
    {
        return self::ALREADY_VERIFIED_ORDER_RESPONSE_CODE;
    }

    /**
     * Sends API request
     *
     * @param string $action
     * @param array $params
     * @param array $data
     * @param string $httpRequest
     * @param array $headers
     * @return array
     * @codingStandardsIgnoreStart
     */
    public function sendRequest(
        $action,
        $params = array(),
        $data = array(),
        $httpRequest = Varien_Http_Client::GET,
        $headers = array()
    ) {
        // @codingStandardsIgnoreEnd
        $result = array();
        $queryString = array();
        $skipDefaultContentTypeHeader = false;
        $skipDefaultAcceptHeader = false;

        $logData = array(
            'http_request' => $httpRequest
        );

        $httpClientConfig = array(
            'maxredirects' => 0,
            'timeout' => 30
        );

        try {
            $httpClient = new Zend_Http_Client();

            if ($params) {
                $logData['params'] = $params;

                if (isset($params['url_params']) && $action) {
                    $action = vsprintf($action, $params['url_params']);
                }

                if (isset($params['query_string'])) {
                    $queryString = $params['query_string'];
                }

                if (!isset($params['url_params']) && !isset($params['query_string'])) {
                    $queryString = $params;
                }
            }

            $url = self::BASE_URL;
            if ($action) {
                $url .= $action;
            }

            $logData['url'] = $url;

            $httpClient->setUri($url)
                ->setConfig($httpClientConfig);

            $authorizationHeader = $this->getApiAuthorizationHeader();
            $httpClient->setHeaders($authorizationHeader['field'], $authorizationHeader['value']);

            if ($headers) {
                if ($this->isHeaderFieldUsed('Content-Type', $headers)) {
                    $skipDefaultContentTypeHeader = true;
                }

                if ($this->isHeaderFieldUsed('Accept', $headers)) {
                    $skipDefaultAcceptHeader = true;
                }

                $logData['headers'] = $headers;

                $httpClient->setHeaders($headers);
            }

            if (!$skipDefaultContentTypeHeader) {
                $logData['headers'][] = 'Content-Type: ' . self::DEFAULT_HEADER_CONTENT_TYPE;
                $httpClient->setHeaders('Content-Type', self::DEFAULT_HEADER_CONTENT_TYPE);
            }

            if (!$skipDefaultAcceptHeader) {
                $logData['headers'][] = 'Accept: ' . self::DEFAULT_HEADER_ACCEPT;
                $httpClient->setHeaders('Accept', self::DEFAULT_HEADER_ACCEPT);
            }

            if ($queryString) {
                $logData['query_string'] = $queryString;

                switch ($httpRequest) {
                    case Varien_Http_Client::GET:
                        $httpClient->setParameterGet($queryString);
                        break;
                    case Varien_Http_Client::POST:
                        $httpClient->setParameterPost($queryString);
                        break;
                }
            }

            if ($data) {
                $logData['raw_data'] = $data;
                $rawData = is_array($data) ? json_encode($data) : $data;
                $httpClient->setRawData($rawData, self::REQUEST_DATA_ENC_TYPE);
            }

            $this->log(array('REQUEST' => $logData));

            $response = $httpClient->request($httpRequest);

            if ($response) {
                $result = array(
                    'status' => $response->isSuccessful(),
                    'code' => $response->getStatus(),
                    'message' => $response->getMessage(),
                    'response_body' => json_decode($response->getBody(), true)
                );

                $logData = $result;
                $logData['status'] = $logData['status'] ? 'success' : 'error';
                $this->log(array('RESPONSE' => $logData));
            } else {
                $this->log('[API-ERROR]: Empty response.');
            }
        } catch (Exception $e) {
            $this->log('[API-ERROR]: ' . $e->getMessage());
            $this->log($e);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getApiAuthorizationHeader()
    {
        $apiKey = Mage::helper('keycash_core')->getApiKey();

        $header = array(
            'field' => self::AUTHORIZATION_HEADER_FIELD,
            'value' => 'Bearer ' . $apiKey
        );

        return $header;
    }

    /**
     * Checks whether header field is already provided
     *
     * @return bool
     */
    protected function isHeaderFieldUsed($headerField, $headers)
    {
        $result = false;

        foreach ($headers as $header) {
            if (false !== strpos($header, $headerField)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Logs KeyCash API related data and messages
     *
     * @param mixed $data
     * @param string $logFile
     */
    public function log($data, $logFile = self::API_LOG_FILE)
    {
        Mage::helper('keycash_core')->log($data, $logFile);
    }
}
