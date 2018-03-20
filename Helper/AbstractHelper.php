<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
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

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->templateModel = $templateModel;
        $this->templateConfig = $templateConfig;
        $this->objectManager = $objectManager;
    }

    public function getSettingsVal($val, $storeId = null)
    {
        if ($storeId) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore($storeId)->getCode());
        }
        
        if ($storeCode = $this->_request->getParam('store')) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        }

        if ($orderId = $this->_request->getParam('order_id')) {
            $storeCode = $this->getStoreCodeFromOrderId($orderId);
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        }

        if ($websiteCode = $this->_request->getParam('website')) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getCode());
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
