<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;
use Magento\Framework\ObjectManagerInterface;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

abstract class AbstractHelper extends MageAbstractHelper
{
    /** @var StoreManager  */
    protected $storeManager;

    /** @var Logger  */
    protected $logger;

    /** @var TemplateModel */
    protected $templateModel;

    /** @var TemplateConfig */
    protected $templateConfig;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var ScopeResolver  */
    protected $scopeResolver;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ScopeResolver $scopeResolver
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->templateModel = $templateModel;
        $this->templateConfig = $templateConfig;
        $this->objectManager = $objectManager;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * @param $val
     * @return mixed|null
     */
    public function getSettingsVal($val)
    {
        if ($storeId = $this->scopeResolver->resolveRequestedStoreId()) {
            $storeCode = $this->storeManager->getStore($storeId)->getCode();
            $this->_logger->info("Logging store $storeCode");
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        }

        if ($websiteCode = $this->scopeResolver->resolveWebsiteId()) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->_logger->info("Logging website $websiteCode with store $storeId");
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->scopeConfig->getValue($val);
    }
    
}
