<?php
/**
 * Kiwiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at the following URI:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the PHP License and are unable to
 * obtain it through the web, please send a note to contact@kiwiz.io
 * so we can mail you a copy immediately.
 *
 * @author     Kiwiz <contact@kiwiz.io>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Kwz\Certification\Model;

use Http\Client\Exception\HttpException;
use Kwz\Certification\Exception\ApiException;
use Kwz\Certification\Helper\EmailFailure;
use Kwz\Certification\Model\Flag\KiwizConfigured;
use Kwz\Certification\Model\Flag\Time;
use Kwz\Certification\Model\Flag\TokenFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Kwz\Certification\Exception\TokenException;
use Kwz\Certification\Helper\Kiwiz;
use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Zend\Json\Json;
use Magento\Framework\App\Config\Storage;

class Client implements ApiInterface
{
    const STORE_FIELD = 'store_id';
    const LOG_ERROR = 'logError';
    const LOG_INFO = 'logInfo';

    protected $httpclient;
    protected $token = null;
    protected $storeId = null;
    protected $uri = null;
    protected $method = null;
    protected $query;
    protected $attachements = [];
    protected $args;
    protected $scopeConfig;
    protected $config;
    protected $cache;
    protected $helper;
    protected $emailFailure;
    protected $timeFlag;
    protected $kiwizConfigured;
    protected $tokenFlagFactory;
    protected $tokenFlag = null;

    protected $tryResetToken = false;

    public function __construct(
        HttpClient $httpClient,
        ScopeConfigInterface $scopeConfig,
        Storage\WriterInterface $config,
        TypeListInterface $cache,
        Kiwiz $helper,
        EmailFailure $emailFailure,
        Time $timeFlag,
        KiwizConfigured $kiwizConfigured,
        TokenFactory $tokenFlagFactory
    ) {
        $this->httpclient = $httpClient;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->cache = $cache;
        $this->helper = $helper;
        $this->emailFailure = $emailFailure;
        $this->timeFlag = $timeFlag->loadSelf();
        $this->kiwizConfigured = $kiwizConfigured->loadSelf();
        $this->tokenFlagFactory = $tokenFlagFactory;
    }

    protected function setToken()
    {
        $this->token = false;
        $username = $this->helper->getStoreConfig(Kiwiz::CONFIG_PATH_AUTH_USERNAME);
        $password = $this->helper->getStoreConfig(Kiwiz::CONFIG_PATH_AUTH_PASSWORD);
        $subscriptionId = $this->helper->getStoreConfig(Kiwiz::CONFIG_PATH_AUTH_SUBSCRIPTION_ID);
        try {
            $response = $this->postTokenGenerate([
                'username' => $username,
                'password' => $password,
                'subscription_id' => $subscriptionId,
                'store_id' => $this->storeId
            ]);
        } catch (HttpException $exception) {
            $this->helper->logError(__('Impossible to get the token from the API. Check credentials'));
            $this->helper->logError($exception->getMessage());
        }
        //Should not happen since API is supposed to return a 40X code in case of false authentification
        if (empty($response['token'])) {
            if (is_array($response)) {
                $response = implode('/', $response);
            }
            throw new TokenException(__('Token not found in Response body: %1', $response));
        }
        $this->token = trim($response['token']);
        try {
            $this->saveToken($this->helper->encrypt($this->token));
            if (empty($this->kiwizConfigured->getFlagData())) {
                $dateConfiguration = \Zend_Date::now();
                $this->kiwizConfigured->setFlagData($dateConfiguration->get(\Zend_Date::ISO_8601))->save();
            }
        } catch (\Exception $e) {
            $this->helper->logError(__('Error during save of token'));
            $this->helper->logError($e->getMessage(), $e->getTrace());
        }
    }

    protected function saveToken($token)
    {
        if (!empty($token)) {
            $this->tokenFlag->setFlagData((string)$token)->save();
        }
    }

    protected function deleteToken()
    {
        $this->tokenFlag->setFlagData(null)->save();
    }

    public function getToken()
    {
        $token = $this->tokenFlag->getFlagData();
        if (empty($token) && $this->token !== false) {
            $this->setToken();
            $token = $this->tokenFlag->getFlagData();
        }
        $this->token = $this->helper->decrypt($token);
        return $this->token;
    }

    public function setUri($method)
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $methods);
        $methods = $methods[0];
        $method = array_shift($methods);
        $this->method = strtoupper($method);
        $this->uri = $this->helper->getStoreConfig(Kiwiz::CONFIG_PATH_AUTH_APIURL) . '/' . strtolower(implode('/', $methods));
        if (!empty($this->query)) {
            $this->uri .= '?' . http_build_query($this->query);
        }
    }

    protected function getQuery()
    {

        return [
            'platform' => $this->helper->getPlatform(),
            'version' =>  $this->helper->getVersion(),
            'test_mode' => (int) ((bool)$this->helper->getStoreConfig(Kiwiz::CONFIG_PATH_GENERAL_IS_TEST_MODE))
        ];
    }

    public function __call($method, $args)
    {
        try {
            $this->prepareArgs($args);
            $this->helper->setStoreId($this->storeId);
            $this->query = $this->getQuery();
            if ($this->token === null) {
                $this->getToken();
            }
            $this->helper->logInfo($this->token);
            $this->setUri($method);
            $this->httpclient->reset();
            $this->httpclient->setUri($this->uri);
            if (empty($this->method)) {
                throw new ApiException(__('Method must be defined'));
            }
            if (!in_array(
                $this->method,
                [Request::METHOD_GET, request::METHOD_POST, request::METHOD_PUT, request::METHOD_DELETE]
            )) {
                throw new ApiException(__('Unknown method %1', $this->method));
            }
            $this->httpclient->setMethod($this->method);
            $headers = ['Accept' => 'application/json'];
            if (!empty($this->token)) {
                $headers['Authorization'] = $this->token;
            }
            $this->httpclient->setHeaders($headers);
            if ($this->method == Request::METHOD_POST && count($args) > 0) {
                $this->httpclient->setParameterPost($this->args);
                $this->httpclient->setEncType(HttpClient::ENC_URLENCODED);
            }

            if ($this->method == Request::METHOD_GET && count($this->args) > 0) {
                $this->query = array_merge($this->query, $this->args);
                //Additional parameters for get requests
                $this->httpclient->setEncType(HttpClient::ENC_URLENCODED);
                $this->setUri($method);
                $this->httpclient->setUri($this->uri);
            }
            if (!empty($this->attachements)) {
                foreach ($this->attachements as $nameDocument => $document) {
                    $name = $this->helper->getNameDocument($nameDocument);
                    $this->httpclient->setFileUpload(
                        $name,
                        $nameDocument,
                        $document->render(),
                        $this->helper->getCtype($document)
                    );
                    $this->httpclient->setEncType(HttpClient::ENC_FORMDATA);
                }
            }
            $this->logCall(self::LOG_INFO);
            $this->httpclient->send();
            $response = $this->httpclient->getResponse();
            $this->parseResponseCode($response);
            return $this->parseResponse($response);
        } catch (\Zend\Http\Exception\RuntimeException $runtimeException) {
            $this->logCall(self::LOG_ERROR);
            $this->helper->logError($runtimeException->getMessage(), $runtimeException->getTrace());
            $this->emailFailure->notify(nl2br($runtimeException->getMessage()));
            throw $runtimeException;
        } catch (TokenException $tokenException) {
            //Reset of token in case it's expired
            $this->deleteToken();
            $this->token = null;
            if(!$this->tryResetToken && $method != 'postTokenGenerate') {
                $this->tryResetToken = true;
                return $this->__call($method, $args);
            }
            $this->logCall(self::LOG_ERROR);
            $this->helper->logError($tokenException->getMessage(), $tokenException->getTrace());
            $this->emailFailure->notify(nl2br($tokenException->getMessage()));
            throw $tokenException;
        } catch (\Exception $e) {
            $this->logCall(self::LOG_ERROR);
            $this->helper->logError(nl2br($e->getMessage(), $e->getTrace()));
            $this->emailFailure->notify($e->getMessage());
            throw $e;
        }
    }

    protected function parseResponse($response)
    {
        if ($response->getHeaders()->get('Content-type')->getFieldValue() == 'application/json') {
            return Json::decode($response->getBody(), Json::TYPE_ARRAY);
        }
        return $response->getBody();
    }

    protected function parseResponseCode($response)
    {
        $responseBody = $this->parseResponse($response);
        if (is_array($responseBody)) {
            $responseBody = implode('/', $responseBody);
        }
        switch ($response->getStatusCode()) {
            case '200':
                return true;
            case '401':
                throw new TokenException(__('Kiwiz API error: Authentification failed. Status code %1, body: %2', $response->getStatusCode(), $responseBody));
            break;
            case '403':
                throw new TokenException(__('You\'re not allowed to access this resource'));
                break;
            default:
                throw new \Zend\Http\Exception\RuntimeException(
                    __('Kiwiz API error: status code %1, body : %2', $response->getStatusCode(), $responseBody)
                );
        }
    }

    protected function logCall($method)
    {
        $this->helper->$method(str_repeat('-', 100));
        $this->helper->$method('URI', $this->uri);
        $this->helper->$method('Args', $this->helper->sanitize($this->args));
    }

    /**
     * Document fields need to be separated correctly from the args in order to process accordingly
     * @param $args
     */
    protected function prepareArgs($args)
    {
        foreach ($args as $key => $arg) {
            //Args are on the form ["fieldName" => $fieldValue]
            foreach ($arg as $field => $value) {
                if ($value instanceof \Zend_Pdf) {
                    $this->attachements[$field] = $value;
                } else {
                    if ($field == self::STORE_FIELD) {
                        if($this->storeId !== $value) {
                            $this->storeId = $value;
                            $this->token = null;
                            $this->tokenFlag = $this->tokenFlagFactory->create();
                            $this->tokenFlag->setFlagByStore($this->storeId)->loadSelf();
                        }
                    } else {
                        $this->args[$field] = $value;
                    }
                }
            }
        }
    }

    public function getHttpClient()
    {
        return $this->httpclient;
    }
}
