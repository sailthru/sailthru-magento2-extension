<?php

namespace Sailthru\MageSail\Helper;

use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\Context;

use Sailthru\MageSail\MageClient;

class ClientBuilder extends AbstractHelper
{

    /** @var  string */
    protected $_apiKey;

    /** @var string */
    protected $_apiSecret;

    /** @var  MageClient */
    protected $client;

    const XML_API_KEY         = "magesail_config/service/api_key";
    const XML_API_SECRET      = "magesail_config/service/secret_key";
    const API_SUCCESS_MESSAGE = "Successfully Validated!";

    public function __construct(
        Context $context,
        StoreManager $storeManager
    ) {
        parent::__construct($context, $storeManager);
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

    public function getClient()
    {
        return $this->client;
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
}
