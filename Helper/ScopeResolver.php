<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request as WebapiRequest;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class ScopeResolver extends AbstractHelper {

    /** @var State  */
    protected $appState;

    /** @var WebapiRequest  */
    protected $webapiRequest;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var OrderRepositoryInterface  */
    protected $orderRepo;

    /** @var ShipmentRepositoryInterface  */
    protected $shipmentRepo;

    private static $ADMIN_AREAS = [
        Area::AREA_ADMINHTML,
        Area::AREA_WEBAPI_REST,
        Area::AREA_WEBAPI_SOAP
    ];

    public function __construct(
        Context $context,
        State $appState,
        WebapiRequest $webapiRequest,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepo,
        ShipmentRepositoryInterface $shipmentRepo
    ) {
        parent::__construct($context);
        $this->appState = $appState;
        $this->webapiRequest = $webapiRequest;
        $this->storeManager = $storeManager;
        $this->orderRepo = $orderRepo;
        $this->shipmentRepo = $shipmentRepo;
    }

    public function getScope()
    {
        if ($store = $this->getStore()) {
            return [$store->getCode(), ScopeInterface::SCOPE_STORE];

        } elseif ($website = $this->getWebsite()) {
            return [$website->getCode(), ScopeInterface::SCOPE_WEBSITE];
        }

        return [null, ScopeInterface::SCOPE_STORES];
    }

    /**
     * @return StoreInterface|null
     */
    public function getStore()
    {
        if ($this->isFrontendArea()){
            return $this->_getStore();

        } elseif ($storeId = $this->getRequestStoreScope()) {
            return $this->_getStore($storeId);

        } elseif ($this->isSalesRequest()) {
            $storeId =  $this->getStoreIdFromSalesRequest();
            return $this->_getStore($storeId);

        } elseif ($website = $this->getWebsite() and $storeId = $this->getWebsiteSingleStoreId($website)) {
            return $this->_getStore($storeId);
        }

        return null;
    }

    /**
     * @return WebsiteInterface|null
     */
    public function getWebsite()
    {
        if ($this->isFrontendArea() and $website = $this->_getWebsite()) {
            return $website;

        } elseif ($wid = $this->getRequestWebsiteScope()) {
            return $this->_getWebsite($wid);
        }

        return null;
    }

    protected function isSalesRequest()
    {
        return $this->getRequestOrderId() || $this->getRequestShipmentId();
    }

    protected function getStoreIdFromSalesRequest()
    {
        $orderId = $this->getRequestOrderId();
        $shipmentId = $this->getRequestShipmentId();
        $entityId = $orderId ?: $shipmentId;
        $repository = $orderId ? $this->orderRepo : $this->shipmentRepo;

        try {
            $entity = $repository->get($entityId);
        } catch (\Exception $e) {
            $this->_logger->error("Error resolving sales scope.", $e);
            return null;
        }

        return $entity->getStoreId();
    }

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
    
    protected function getRequestOrderId()
    {
        return $this->_request->getParam('order_id') ?: $this->webapiRequest->getParam('orderId');
    }

    protected function getRequestShipmentId()
    {
        return $this->_request->getParam("shipment_id");
    }

    /**
     * @param WebsiteInterface $website
     * @return null|int
     */
    protected function getWebsiteSingleStoreId(WebsiteInterface $website)
    {
       if ($website instanceof Website) {
           $storeIds = $website->getStoreIds();
           if ($storeIds and count($storeIds()) == 1) {
               return $storeIds[0];
           }
       }
       return null;
    }

    /**
     * @return null|string
     */
    private function getAreaCode()
    {
        try {
            return $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            $this->_logger->error("Error getting area code: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * @param null|int $storeId
     * @return StoreInterface
     */
    private function _getStore($storeId = null)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * @param null|int $websiteId
     * @return null|WebsiteInterface
     */
    private function _getWebsite($websiteId = null) {
        try {
            return $this->storeManager->getWebsite($websiteId);
        } catch (LocalizedException $e) {
            $this->_logger->error("Error getting website: {$e->getMessage()}");
            return null;
        }
    }

}