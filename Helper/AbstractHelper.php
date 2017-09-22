<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

class AbstractHelper extends MageAbstractHelper
{
    /** @var StoreManager  */
    protected $storeManager;

    /** @var Logger  */
    protected $logger;

    /** @var TemplateModel */
    protected $templateModel;

    /** @var TemplateConfig */
    protected $templateConfig;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->templateModel = $templateModel;
        $this->templateConfig = $templateConfig;
    }

    public function getSettingsVal($val, $storeId = null)
    {
        $scopeType = ScopeInterface::SCOPE_STORES;
        $scopeCode = null;

        if ($storeId) {
            $scopeCode = $this->storeManager->getStore($storeId)->getCode();
        } else {
            $storeCode = $this->_request->getParam('store');
            $websiteCode = $this->_request->getParam('website');
            if ($storeCode) {
                $scopeType = ScopeInterface::SCOPE_STORE;
                $scopeCode = $storeCode;
            } elseif ($websiteCode) {
                $scopeType = ScopeInterface::SCOPE_WEBSITE;
                $scopeCode = $websiteCode;
            } else {
                $scopeCode = $this->storeManager->getStore()->getCode();
            }
        }
        return $this->scopeConfig->getValue($val, $scopeType, $scopeCode);
    }
}
