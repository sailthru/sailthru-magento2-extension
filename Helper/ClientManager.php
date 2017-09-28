<?php

namespace Sailthru\MageSail\Helper;

use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\MageClient;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

class ClientManager extends AbstractHelper
{

    /** @var  MageClient */
    protected $client;

    const XML_API_KEY         = "magesail_config/service/api_key";
    const XML_API_SECRET      = "magesail_config/service/secret_key";
    const API_SUCCESS_MESSAGE = "Successfully Validated!";

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $logger,
            $templateModel,
            $templateConfig,
            $objectManager
        );
        $this->initClient();
    }

    public function initClient($storeId = null)
    {
        $apiKey = $this->getSettingsVal(self::XML_API_KEY, $storeId);
        $apiSecret = $this->getSettingsVal(self::XML_API_SECRET, $storeId);
        $this->client = new MageClient($apiKey, $apiSecret, $this->logger, $this->storeManager);
    }

    public function getClient($update=false, $storeId = null)
    {
        if ($update) {
            $this->initClient($storeId);
        }
        return $this->client;
    }

    public function apiValidate()
    {
        try {
            $result = $this->client->getSettings();
            if (!array_key_exists("error", $result)) {
                return [1, self::API_SUCCESS_MESSAGE];
            } else {
                return 0;
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
