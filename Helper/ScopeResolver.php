<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request as WebapiRequest;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ScopeResolver extends AbstractHelper {

    /** @var State  */
    protected $appState;

    /** @var WebapiRequest  */
    protected $webapiRequest;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    private static $ADMIN_AREAS = [
        Area::AREA_ADMINHTML,
        Area::AREA_WEBAPI_REST,
        Area::AREA_WEBAPI_SOAP
    ];

    public function __construct(
        Context $context,
        State $appState,
        WebapiRequest $webapiRequest,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->appState = $appState;
        $this->webapiRequest = $webapiRequest;
        $this->storeManager = $storeManager;
    }

    public function getScopeStore()
    {
        if ($this->isFrontendArea()){
            return $this->storeManager->getStore()->getCode();
        }

        if ($storeId = $this->getRequestStoreScope()) {
            return $this->storeManager->getStore($storeId);
        }
    }

    /**
     * Check if area code is an admin area code (including API calls)
     * @return bool
     */
    protected function isAdminArea()
    {
            return in_array($this->getAreaCode(), self::$ADMIN_AREAS);
    }

    protected function isFrontendArea()
    {
        return $this->getAreaCode() === "frontend";
    }

    protected function getRequestStoreScope()
    {
        return $this->_request->getParam("store");
    }

    protected function getRequestWebsiteScope()
    {
        return $this->_request->getParam("website");
    }

    protected function getRequestOrderScope()
    {
        return $this->_request->getParam('order_id') ?: $this->webapiRequest->getParam('orderId');
    }

    private function getAreaCode()
    {
        try {
            return $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            $this->_logger->error("Error getting area code: {$e->getMessage()}");
            return false;
        }
    }

}