<?php

namespace Sailthru\MageSail\Helper\Product;

use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Configurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\ProductData;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\MageClient;

abstract class RequestAbstract extends AbstractHelper
{
    /**
     * @var string
     */
    protected $eventType;

    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var Configurable
     */
    protected $configurableProduct;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var CatalogProductHelper
     */
    protected $productHelper;

    /**
     * @var ProductData
     */
    protected $sailthruProduct;

    /**
     * @var Settings
     */
    protected $sailthruSettings;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param ClientManager $clientManager
     * @param Configurable $configurableProduct
     * @param ImageHelper $imageHelper
     * @param CatalogProductHelper $productHelper
     * @param ProductData $sailthruProduct
     * @param Settings $sailthruSettings
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ClientManager $clientManager,
        Configurable $configurableProduct,
        ImageHelper $imageHelper,
        CatalogProductHelper $productHelper,
        SailthruProduct $sailthruProduct,
        Settings $sailthruSettings,
        StoreManagerInterface $storeManager
    ) {
        $this->clientManager = $clientManager;
        $this->configurableProduct = $configurableProduct;
        $this->imageHelper = $imageHelper;
        $this->productHelper = $productHelper;
        $this->sailthruProduct = $sailthruProduct;
        $this->sailthruSettings = $sailthruSettings;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * To send multiple requests to API.
     *
     * @param ProductModel $product
     *
     * @return RequestAbstract
     */
    public function sendMultipleRequests(ProductModel $product)
    {
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            $this->sendRequest($product, (int)$storeId);
        }

        return $this;
    }

    /**
     * To send single request to API.
     *
     * @param ProductModel $product
     * @param int|null $storeId
     *
     * @return RequestAbstract
     */
    public function sendRequest(ProductModel $product, int $storeId = null)
    {
        $storeId = $storeId ?? $product->getStoreId();
        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        if (empty($storeId)) {
            $storeScopes = $product->getStoreIds();
            $storeId = $this->_request->getParam('store') ?: $storeScopes[0];
            if (!empty($storeId)) {
                $product->setStoreId($storeId);
            }
        }

        $client = $this->clientManager->getClient($storeId);
        $client->_eventType = $this->eventType;
        try {
            if (empty($this->validateProduct($product, $storeId))) {
                return $this;
            }
            $data = $this->getProductData($product, $storeId);
            if (empty($data)) {
                return $this;
            }
            $this->executeRequest($client, $data);
        } catch (\Sailthru_Client_Exception $e) {
            $client->logger($e);
        }

        return $this;
    }

    /**
     * Execute single request to API.
     *
     * @param MageClient $client
     * @param array $data
     *
     * @return RequestAbstract
     */
    abstract protected function executeRequest(MageClient $client, array $data);

    /**
     * Validate Product data.
     *
     * @param ProductModel $product
     * @param int $storeId
     *
     * @return boolean
     */
    public function validateProduct(ProductModel $product, int $storeId = null)
    {
        if (empty($this->sailthruProduct->isProductInterceptOn($storeId))) {
            return false;
        }
        if ($product->getTypeId() == 'configurable'
            && empty($this->sailthruProduct->canSyncMasterProducts($storeId))
        ) {
            return false;
        }
        if ($this->sailthruProduct->isVariant($product)
            && empty($this->sailthruProduct->canSyncVariantProducts($storeId))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get Product data.
     *
     * @param ProductModel $product
     * @param int $storeId
     *
     * @return array
     */
    abstract public function getProductData(ProductModel $product, int $storeId = null);
}
