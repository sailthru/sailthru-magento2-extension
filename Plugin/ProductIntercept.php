<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Configurable;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Product as SailthruProduct;
use Sailthru\MageSail\Helper\Settings;

class ProductIntercept
{
    /** Store code for `All Store Views`. */
    const ALL_STORE_VIEWS_CODE = 'admin';

    /** Name of the `save product` event. */
    const SAVE_PRODUCT_EVENT_NAME = 'SaveProduct';

    /** Text for `product data` error. */
    const PRODUCT_DATA_ERROR_TEXT = 'ProductData Error';

    // Need some things from EAV attributes which seems more intensive. Attributes below,
    // we'd rather get from Product.
    public static $unusedVarKeys = [
        'status',
        'row_id',
        'type_id',
        'attribute_set_id',
        'media_gallery',
        'thumbnail',
        'shipment_type',
        'url_key',
        'price_view',
        'msrp_display_actual_price_type',
        'page_layout',
        'options_container',
        'custom_design',
        'custom_layout',
        'gift_message_available',
        'category_ids',
        'image',
        'small_image',
        'visibility',
        'relatedProductIds',
        'upSellProductIds',
        'description',
        'meta_keyword',
        'name',
        'created_at',
        'updated_at',
        'tax_class_id',
        'quantity_and_stock_status',
        'sku'
    ];

    /** @var Magento\Framework\Url */
    private $frameworkUrl;

    /** @var Sailthru\MageSail\Helper\Settings */
    private $sailthruSettings;

    public function __construct(
        ClientManager $clientManager,
        SailthruProduct $sailthruProduct,
        StoreManagerInterface $storeManager,
        ProductHelper $productHelper,
        ImageHelper $imageHelper,
        Configurable $cpModel,
        Context $context,
        Url $frameworkUrl,
        Settings $sailthruSettings
    ) {
        $this->clientManager    = $clientManager;
        $this->sailthruProduct  = $sailthruProduct;
        $this->_storeManager    = $storeManager;
        $this->productHelper    = $productHelper;
        $this->imageHelper      = $imageHelper;
        $this->cpModel          = $cpModel;
        $this->context          = $context;
        $this->request          = $context->getRequest();
        $this->frameworkUrl     = $frameworkUrl;
        $this->sailthruSettings = $sailthruSettings;
    }

    public function afterAfterSave(Product $productModel, $productResult)
    {
        if ($this->sailthruProduct->isProductInterceptOn()) {
            if ($this->isAllStoreViews($productModel)) {
                $this->sendMultipleRequests($productModel, $productResult);
            } else {
                $this->sendRequest($productModel);
            }
        }

        return $productResult;
    }

    /**
     * To send multiple requests to API.
     * 
     * @param  Product                        $product
     * @param  Magento\Catalog\Model\Product  $productResult
     */
    private function sendMultipleRequests(Product $product, $productResult)
    {
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if ($this->sailthruSettings->getTransactionalsEnabled($storeId)) {
                $this->sendRequest($productResult, $storeId);
            }
        }
    }

    /**
     * To send single request to API.
     * 
     * @param  Magento\Catalog\Model\Product $productResult
     * @param  string|null                   $storeId
     */
    private function sendRequest($productResult, $storeId = null)
    {
        $data = $this->getProductData($productResult, $storeId);
        if ($data) {
            try {
                $this->clientManager->getClient()->_eventType = self::SAVE_PRODUCT_EVENT_NAME;
                $this->clientManager->getClient()->apiPost('content', $data);
            } catch (\Sailthru_Client_Exception $e) {
                $this->clientManager->getClient()->logger(self::PRODUCT_DATA_ERROR_TEXT);
                $this->clientManager->getClient()->logger($e->getMessage());
            }
        }
    }

    /**
     * To check if product is in "All Store Views" scope.
     * 
     * @param  Product $product
     * 
     * @return bool
     */
    private function isAllStoreViews(Product $product)
    {
        return self::ALL_STORE_VIEWS_CODE == $this->_storeManager->getStore($product->getStoreId())->getCode()
            ? true
            : false;
    }

    /**
     * Create Product array for Content API
     *
     * @param Product $product
     * @return array|false
     */
    public function getProductData(Product $product, $storeId = null)
    {
        $productType = $product->getTypeId();
        $isMaster = ($productType == 'configurable');
        $updateMaster = $this->sailthruProduct->canSyncMasterProducts();
        if ($isMaster and !$updateMaster) {
            return false;
        }

        $isSimple = ($productType == 'simple');
        $parents = $this->cpModel->getParentIdsByChild($product->getId());
        $isVariant = ($isSimple and $parents);
        $updateVariants = $this->sailthruProduct->canSyncVariantProducts();
        $this->clientManager->getClient()->logger($updateVariants);
        if ($isVariant and !$updateVariants) {
            return false;
        }

        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        if (!$storeId) {
            $storeScopes = $product->getStoreIds();
            $storeId = $this->request->getParam('store') ?: $storeScopes[0];
            if ($storeId) {
                $product->setStoreId($storeId);
            }
        }
        $this->_storeManager->setCurrentStore($storeId);

        $attributes = $this->sailthruProduct->getProductAttributeValues($product);
        $categories = $this->sailthruProduct->getCategories($product);

        try {
            $data = [
                'url'   => $isVariant ? $this->getProductFragmentedUrl($product, $parents[0]) :
                    $this->getProductUrl($product, $storeId, true),
                'title' => htmlspecialchars($product->getName()),
                'spider' => 0,
                'price' => $price = ($product->getPrice() ? $product->getPrice() :
                    $product->getPriceInfo()->getPrice('final_price')->getValue()) * 100,
                'description' => strip_tags($product->getDescription()),
                'tags' => $this->sailthruProduct->getTags($product, $attributes, $categories),
                'images' => [],
                'vars' => [
                    'isMaster' => (int) $isMaster,
                    'isVariant' =>(int) $isVariant,
                    'sku' => $product->getSku(),
                    'weight'  => $product->getWeight(),
                    'storeId' => $product->getStoreId(),
                    'typeId' => $product->getTypeId(),
                    'status' => $product->getStatus(),
                    'categories' => $categories,
                    'websiteIds' => $product->getWebsiteIds(),
                    'storeIds'  => $product->getStoreIds(),
                    'price' => $product->getPrice() * 100,
                    'groupPrice' => $product->getGroupPrice(),
                    'formatedPrice' => $product->getFormatedPrice(),
                    'calculatedFinalPrice' => $product->getCalculatedFinalPrice(),
                    'minimalPrice' => $product->getMinimalPrice(),
                    'specialPrice' => $product->getSpecialPrice(),
                    'specialFromDate' => $product->getSpecialFromDate(),
                    'specialToDate'  => $product->getSpecialToDate(),
                    'relatedProductIds' => $product->getRelatedProductIds(),
                    'upSellProductIds' => $product->getUpSellProductIds(),
                    'crossSellProductIds' => $product->getCrossSellProductIds(),
                    'isConfigurable'  => (int) $product->canConfigure(),
                    'isSalable' => (int) $product->isSalable(),
                    'isAvailable'  => (int) $product->isAvailable(),
                    'isVirtual'  => (int) $product->isVirtual(),
                    'isInStock'  => (int) $product->isInStock(),
                    'isVisible' => (int) $this->productHelper->canShow($product)
                ] + $attributes,
            ];

            if ($isVariant) {
                $data['inventory'] = $product->getStockData()["qty"];
            }

            // Add product images
            if ($image = $product->getImage()) {
                $data['images']['thumb'] = [
                    "url" => $this->imageHelper->init($product, 'product_listing_thumbnail')->getUrl()
                ];
                $data['images']['full'] = [
                    "url"=> $this->getBaseImageUrl($product)
                ];
            }
            if ($parents and count($parents) == 1) {
                $data['vars']['parentID'] = $parents[0];
            }

            return $data;
        } catch (\Exception $e) {
            $this->clientManager->getClient()->logger($e->getMessage());
            return false;
        }
    }

    public function getProductFragmentedUrl(Product $product, $parent)
    {
        $parentUrl = $this->productHelper->getProductUrl($parent);
        $pSku = $product->getSku();
        return "{$parentUrl}#{$pSku}";
    }

    /**
     * To get product url.
     * 
     * @param  Product $product
     * @param  string  $storeId
     * @param  bool    $useSID
     * 
     * @return string
     */
    public function getProductUrl(Product $product, $storeId, $useSID = false)
    {
        # to get not empty request path
        $product->setStoreId($storeId)->getProductUrl($useSID);
        # to get product url
        return preg_replace('/\?SID=(.*?)(?:[@:*]|$)/', '', $this->frameworkUrl->getUrl('', [
            '_direct' => $product->getRequestPath(),
            '_query' => [],
        ]));
    }

    // Magento 2 getImage seems to add a strange slash, therefore this.
    public function getBaseImageUrl($product)
    {
        return $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
    }
}
