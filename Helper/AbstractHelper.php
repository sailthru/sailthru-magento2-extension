<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;
use Magento\Store\Model\WebsiteRepository;
use Sailthru\MageSail\Logger;

abstract class AbstractHelper extends MageAbstractHelper
{
    /** @var StoreManager  */
    protected $storeManager;

    /** @var Logger  */
    protected $logger;

    /** @var ScopeResolver  */
    protected $scopeResolver;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        ScopeResolver $scopeResolver
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Retrieve a properly scoped Magento config value.
     * @param string $val System Config Path
     * @param int $storeId Store ID
     * @return mixed
     */
    public function getSettingsVal($val, $storeId = null)
    {
        if ($storeId) {
            $storeCode = $this->storeManager->getStore($storeId)->getCode();
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        }

        if ($storeId = $this->scopeResolver->resolveRequestedStoreId()) {
            $storeCode = $this->storeManager->getStore($storeId)->getCode();
            $this->_logger->info("Using Store ID $storeId");
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        }

        $websiteId = $this->scopeResolver->resolveWebsiteId();
        if ($websiteId and $websiteCode = $this->getWebsiteCode($websiteId)) {
            /** @var WebsiteRepository $websiteRepo */
            $this->_logger->info("Using Website ID $websiteId");
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        $this->_logger->info("Using default scope");
        $storeCode = $this->storeManager->getStore()->getCode();
        return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
    }

    private function getWebsiteCode($websiteId)
    {
        try {
            return $this->storeManager->getWebsite($websiteId)->getCode();
        } catch (LocalizedException $ex) {
            $this->logger->err("Sailthru Website Resolver Error: {$ex->getMessage()}");
            return null;
        }
    }
    
}
