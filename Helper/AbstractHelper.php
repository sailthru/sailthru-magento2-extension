<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;
use Magento\Framework\ObjectManagerInterface;
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
        if ($storeId = $this->scopeResolver->resolveStoreId()) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeId);
        }

        if ($websiteCode = $this->scopeResolver->resolveWebsiteId()) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return null;
    }

    private function getStoreCodeFromOrderId($orderId) {
        /** @var CollectionFactoryInterface $orderFactory */
        $orderFactory = $this->objectManager->create('\Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface');
        $order = $orderFactory->create()
                              ->addAttributeToSelect("store_id")
                              ->addFilter("entity_id", $orderId)
                              ->getFirstItem();
        $storeId = $order->getStoreId();
        return $this->storeManager->getStore($storeId)->getCode();
    }
}
