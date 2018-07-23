<?php

namespace Sailthru\MageSail\Helper;

use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\MageClient;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

class ClientManager extends AbstractHelper
{

    const XML_API_KEY         = "magesail_config/service/api_key";
    const XML_API_SECRET      = "magesail_config/service/secret_key";
    const XML_JS_ENABLED      = "magesail_config/js/enabled";
    const XML_JS_CUSTOMER_ID  = "magesail_config/js/customer_id";
    const API_SUCCESS_MESSAGE = "Successfully Validated!";

    /** @var  MageClient */
    protected $client;

    /** @var ModuleListInterface */
    private $moduleList;

    /** @var  array */
    private $settings;

    /** @var  string */
    private $customerId;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ModuleListInterface $moduleList,
        ScopeResolver $scopeResolver
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $logger,
            $templateModel,
            $templateConfig,
            $objectManager,
            $scopeResolver
        );
        $this->moduleList = $moduleList;
    }

    public function initClient($storeId = null)
    {
        $apiKey = $this->getSettingsVal(self::XML_API_KEY, $storeId);
        $apiSecret = $this->getSettingsVal(self::XML_API_SECRET, $storeId);
        $this->client = new MageClient($apiKey, $apiSecret, $this->getSetupVersion(), $this->logger, $this->scopeResolver);
    }

    public function getClient($update=false, $storeId = null)
    {
        if ($update or !$this->client) {
            $this->initClient($storeId);
        }
        return $this->client;
    }

    public function getSettings($update = false)
    {
        if ($update || empty($this->settings)) {
            $this->settings = $this->getClient()->getSettings();
        }
        return $this->settings;
    }

    public function isJsEnabled()
    {
        return $this->getSettingsVal(self::XML_JS_ENABLED);
    }

    public function getCustomerId()
    {
        return $this->getSettingsVal(self::XML_JS_CUSTOMER_ID);
    }

    public function useJs()
    {
        return $this->isJsEnabled() && !empty($this->getCustomerId());
    }

    public function apiValidate()
    {
        try {
            $result = $this->getSettings(true);
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

    private function getSetupVersion()
    {
        $moduleData = $this->moduleList->getOne('Sailthru_MageSail');
        return isset($moduleData['setup_version'])
            ? $moduleData['setup_version']
            : "";
    }

}
