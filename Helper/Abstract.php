<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;

use Sailthru\MageSail\MageClient;

class AbstractHelper extends MageAbstractHelper
{

    /** @var  string */
    protected $_apiKey;

    /** @var string */
    protected $_apiSecret;

    /** @var  MageClient */
    public $client;

    const XML_API_KEY                  = "magesail_config/service/api_key";
    const XML_API_SECRET               = "magesail_config/service/secret_key";
    const API_SUCCESS_MESSAGE          = "Successfully Validated!";

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_apiKey = $this->getApiKey();
        $this->_apiSecret = $this->getApiSecret();
        $this->initClient();
    }

    private function getApiKey()
    {
        return $this->getSettingsVal(self::XML_API_KEY);
    }

    private function getApiSecret()
    {
        return $this->getSettingsVal(self::XML_API_SECRET);
    }

    public function initClient()
    {
        $this->client = new MageClient(
            $this->_apiKey,
            $this->_apiSecret,
            '/var/log/sailthru.log'
        );
    }

    public function apiValidate()
    {
        try {
            $result = $this->client->getSettings();
            if (!array_key_exists("error", $result)) {
                return [1, self::API_SUCCESS_MESSAGE];
            }
        } catch (\Exception $e) {
            return [0, $e->getMessage()];
        }
    }

    public function isValid()
    {
        $check = $this->apiValidate();
        return $check[0];
    }

    public function logger($message)
    {
        $this->client->logger($message);
    }

    public function getSettingsVal($val)
    {
        $storeCode = $this->_request->getParam('store');
        $websiteCode = $this->_request->getParam('website');
        if ($storeCode) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        } elseif ($websiteCode) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }
        return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORES);
    }
}
