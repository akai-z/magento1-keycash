<?php

class Keycash_Core_Helper_Api extends Mage_Core_Helper_Abstract
{
    const BASE_URL = 'https://api.keycash.co/api/v1/';

    const AUTHORIZATION_HEADER_FIELD = 'authorization';
    const DEFAULT_HEADER_CONTENT_TYPE = 'application/json';
    const DEFAULT_HEADER_ACCEPT = 'application/json';
    const REQUEST_DATA_ENC_TYPE = 'application/json';

    const ORDER_CREATE_ACTION = 'orders';
    const ORDER_RETRIEVE_ACTION = 'orders/%s';
    const ORDER_UPDATE_ACTION = 'orders/%s';
    const ORDER_VERIFY_ACTION = 'orders/%s/verify';

    const ORDER_RETRIEVE_ACTION_VERIFICATION_PARAM = '?include=verifications';

    const ORDER_VERIFICATION_DEFAULT_TYPE = 'bestway';

    const ALREADY_VERIFIED_ORDER_RESPONSE_CODE = 422;

    const API_LOG_FILE = 'keycash_api.log';

    public function getOrderCreateAction()
    {
        return self::ORDER_CREATE_ACTION;
    }

    public function getOrderRetrieveAction($isIncludeVerifications = false)
    {
        $includeVerificationsParam = $isIncludeVerifications
            ? self::ORDER_RETRIEVE_ACTION_VERIFICATION_PARAM
            : '';

        return self::ORDER_RETRIEVE_ACTION . $includeVerificationsParam;
    }

    public function getOrderUpdateAction()
    {
        return self::ORDER_UPDATE_ACTION;
    }

    public function getOrderVerifyAction()
    {
        return self::ORDER_VERIFY_ACTION;
    }

    public function getOrderVerificationDefaultType()
    {
        return self::ORDER_VERIFICATION_DEFAULT_TYPE;
    }

    public function getAlreadyVerifiedOrderResponseCode()
    {
        return self::ALREADY_VERIFIED_ORDER_RESPONSE_CODE;
    }

    public function sendRequest(
        $action,
        $params = array(),
        $data = array(),
        $httpRequest = Varien_Http_Client::GET,
        $headers = array()
    ) {
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

    protected function getApiAuthorizationHeader()
    {
        $apiKey = Mage::helper('keycash_core')->getApiKey();

        $header = array(
            'field' => self::AUTHORIZATION_HEADER_FIELD,
            'value' => 'Bearer ' . $apiKey
        );

        return $header;
    }

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

    public function log($data, $logFile = self::API_LOG_FILE)
    {
        Mage::helper('keycash_core')->log($data, $logFile);
    }
}
