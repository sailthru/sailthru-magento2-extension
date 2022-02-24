<?php

namespace Sailthru\MageSail\Helper;

use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Configurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use Sailthru\MageSail\Helper\Settings;

class Product extends AbstractHelper
{
    const EVENT_TYPE_SAVE_PRODUCT   = 'SaveProduct';
    const EVENT_TYPE_DELETE_PRODUCT = 'DeleteProduct';

    const METHOD_TYPES = [
        self::EVENT_TYPE_SAVE_PRODUCT   => 'Post',
        self::EVENT_TYPE_DELETE_PRODUCT => 'Delete',
    ];

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
     * Send post content to Sailthru
     *
     * @param ProductModel $product
     *
     * @return Product
     *
     * @throws NoSuchEntityException
     */
    public function postContent(ProductModel $product)
    {
        if ($this->isAllStoreViews($product)) {
            $this->sendMultipleRequests($product, self::EVENT_TYPE_SAVE_PRODUCT);
        } else {
            if ($this->sailthruProduct->isProductInterceptOn($product->getStoreId())) {
                $this->sendRequest($product, self::EVENT_TYPE_SAVE_PRODUCT);
            }
        }

        return $this;
    }

    /**
     * Send delete content to Sailthru
     *
     * @param ProductModel $product
     *
     * @return Product
     *
     * @throws NoSuchEntityException
     */
    public function deleteContent(ProductModel $product)
    {
        if ($this->isAllStoreViews($product)) {
            $this->sendMultipleRequests($product, self::EVENT_TYPE_DELETE_PRODUCT);
        } else {
            if ($this->sailthruProduct->isProductInterceptOn($product->getStoreId())) {
                $this->sendRequest($product, self::EVENT_TYPE_DELETE_PRODUCT);
            }
        }

        return $this;
    }

    /**
     * To send multiple requests to API.
     *
     * @param ProductModel $product
     * @param string $eventType
     *
     * @return Product
     */
    protected function sendMultipleRequests(ProductModel $product, string $eventType)
    {
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            $this->sendRequest($product, $eventType, (int)$storeId);
        }

        return $this;
    }

    /**
     * To send single request to API.
     *
     * @param ProductModel $product
     * @param int|null $storeId
     * @param string $eventType
     *
     * @return Product
     */
    protected function sendRequest(ProductModel $product, string $eventType, int $storeId = null)
    {
        $storeId = $storeId ?? $product->getStoreId();
        if (empty($this->sailthruProduct->isProductInterceptOn($storeId))) {
            return $this;
        }
        if ($eventType == self::EVENT_TYPE_DELETE_PRODUCT
            && empty($this->sailthruProduct->isRemoveInSailthru($storeId))
        ) {
            return $this;
        }
        $client = $this->clientManager->getClient($storeId);
        $data = $this->getProductData($eventType, $product, $storeId);
        if (empty($data)) {
            return $this;
        }
        try {
            $client->_eventType = $eventType;
            $apiMethod = 'api' . self::METHOD_TYPES[$eventType];
            $client->$apiMethod('content', $data);
        } catch (\Sailthru_Client_Exception $e) {
            $client->logger(__('ProductData Error'));
            $client->logger($e->getMessage());
        }

        return $this;
    }

    /**
     * To check if product is in "All Store Views" scope.
     *
     * @param ProductModel $product
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    protected function isAllStoreViews(ProductModel $product)
    {
        return $this->storeManager->getStore($product->getStoreId())->getCode() == 'admin';
    }

    /**
     * Create Product array for Content API
     *
     * @param string $eventType
     * @param ProductModel $product
     * @param int $storeId
     *
     * @return array|false
     */
    protected function getProductData(string $eventType, ProductModel $product, int $storeId = null)
    {
        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        if (!$storeId) {
            $storeScopes = $product->getStoreIds();
            $storeId = $this->_request->getParam('store') ? : $storeScopes[0];
            if ($storeId) {
                $product->setStoreId($storeId);
            }
        }
        $this->storeManager->setCurrentStore($storeId);

        $productType = $product->getTypeId();
        $isMaster = ($productType == 'configurable');
        $updateMaster = $this->sailthruProduct->canSyncMasterProducts($storeId);
        if ($isMaster && !$updateMaster) {
            return false;
        }

        $isVariant = $this->sailthruProduct->isVariant($product);
        $updateVariants = $this->sailthruProduct->canSyncVariantProducts($storeId);
        if ($isVariant && !$updateVariants) {
            return false;
        }

        if ($eventType == self::EVENT_TYPE_DELETE_PRODUCT) {
            return [
                'id' => count($product->getStoreIds()) > 1
                    ? $storeId . "-" . $product->getSku()
                    : $product->getSku(),
                'key' => 'sku',
            ];
        }

        if (!$isVariant && !$this->productHelper->canShow($product)) {
            return false;
        }

        $attributes = $this->sailthruProduct->getProductAttributeValuesForTagsVars($product);
        $categories = $this->sailthruProduct->getCategories($product);

        try {
            $data = [
                'url'         => $this->sailthruProduct->getProductUrl($product, $storeId),
                'keys'        => [
                    'sku' => count($product->getStoreIds()) > 1
                        ? $storeId . "-" . $product->getSku()
                        : $product->getSku()
                ],
                'title'       => htmlspecialchars($product->getName()),
                'spider'      => 0,
                'price'       => ($product->getPrice()
                        ? $product->getPrice()
                        : $product->getPriceInfo()->getPrice('final_price')->getValue()) * 100,
                'description' => strip_tags($product->getDescription()),
                'tags'        => $this->sailthruProduct->getTags($product, $attributes, $categories),
                'images'      => [],
                'vars'        => [
                    'isMaster'             => (int)$isMaster,
                    'isVariant'            => (int)$isVariant,
                    'sku'                  => $product->getSku(),
                    'weight'               => $product->getWeight(),
                    'storeId'              => $product->getStoreId(),
                    'typeId'               => $product->getTypeId(),
                    'status'               => $product->getStatus(),
                    'categories'           => $categories,
                    'websiteIds'           => $product->getWebsiteIds(),
                    'storeIds'             => $product->getStoreIds(),
                    'price'                => $product->getPrice() * 100,
                    'groupPrice'           => $product->getGroupPrice(),
                    'formatedPrice'        => $product->getFormatedPrice(),
                    'calculatedFinalPrice' => $product->getCalculatedFinalPrice(),
                    'minimalPrice'         => $product->getMinimalPrice(),
                    'specialPrice'         => $product->getSpecialPrice(),
                    'specialFromDate'      => $product->getSpecialFromDate(),
                    'specialToDate'        => $product->getSpecialToDate(),
                    'relatedProductIds'    => $product->getRelatedProductIds(),
                    'upSellProductIds'     => $product->getUpSellProductIds(),
                    'crossSellProductIds'  => $product->getCrossSellProductIds(),
                    'isConfigurable'       => (int)$product->canConfigure(),
                    'isSalable'            => (int)$product->isSalable(),
                    'isAvailable'          => (int)$product->isAvailable(),
                    'isVirtual'            => (int)$product->isVirtual(),
                    'isInStock'            => (int)$product->isInStock(),
                ] + $attributes,
            ];

            if ($isVariant) {
                $pIds = $this->configurableProduct->getParentIdsByChild($product->getId());
                if ($pIds && count($pIds) == 1) {
                    $data['vars']['parentID'] = $pIds[0];
                }
            }

            if (!empty($product->getStockData()) && !empty($product->getStockData()['qty'])) {
                $qty = intval($product->getStockData()['qty']);
            } else if (!empty($product->getQty())) {
                $qty = intval($product->getQty());
            }
            if (isset($qty)) {
                $data['inventory'] = $qty > 0 ? $qty : 0;
            }

            // Add product images
            if (!empty($product->getImage())) {
                $data['images']['thumb'] = [
                    'url' => $this->imageHelper->init($product, 'sailthru_thumb')->getUrl()
                ];
                $data['images']['full'] = [
                    'url' => $this->sailthruProduct->getBaseImageUrl($product)
                ];
            }

            return $data;
        } catch (\Exception $e) {
            $this->clientManager->getClient($storeId)->logger($e->getMessage());

            return false;
        }
    }
}
