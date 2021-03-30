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
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use Sailthru\MageSail\Helper\Settings;

class ProductIntercept
{
    /** @var \Sailthru\MageSail\Helper\Settings */
    private $sailthruSettings;

    /** @var Product */
    private $productModel;

    public function __construct(
        ClientManager $clientManager,
        SailthruProduct $sailthruProduct,
        StoreManagerInterface $storeManager,
        ProductHelper $productHelper,
        ImageHelper $imageHelper,
        Configurable $cpModel,
        Context $context,
        Settings $sailthruSettings,
        Product $productModel
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruProduct = $sailthruProduct;
        $this->_storeManager = $storeManager;
        $this->productHelper = $productHelper;
        $this->imageHelper = $imageHelper;
        $this->cpModel = $cpModel;
        $this->context = $context;
        $this->request = $context->getRequest();
        $this->sailthruSettings = $sailthruSettings;
        $this->productModel = $productModel;
    }

    public function afterAfterSave(Product $productModel, $productResult)
    {
        if ($this->isAllStoreViews($productModel)) {
            $this->sendMultipleRequests($productModel, $productResult);
        } else {
            $storeId = $productModel->getStoreId();
            if ($this->sailthruProduct->isProductInterceptOn($storeId)) {
                $this->sendRequest($productModel, $storeId);
            }
        }

        return $productResult;
    }

    /**
     * To send multiple requests to API.
     *
     * @param  Product $product
     * @param  Product $productResult
     */
    private function sendMultipleRequests(Product $product, $productResult)
    {
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if ($this->sailthruProduct->isProductInterceptOn($storeId)) {
                $this->sendRequest($productResult, $storeId);
            }
        }
    }

    /**
     * To send single request to API.
     *
     * @param  Product     $productResult
     * @param  string|null $storeId
     */
    public function sendRequest($productResult, $storeId = null)
    {
        $client = $this->clientManager->getClient($storeId);
        $data = $this->getProductData($productResult, $storeId);
        if ($data) {
            try {
                $client->_eventType = 'SaveProduct';
                $client->apiPost('content', $data);
            } catch (\Sailthru_Client_Exception $e) {
                $client->logger(__('ProductData Error'));
                $client->logger($e->getMessage());
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
        return $this->_storeManager->getStore($product->getStoreId())->getCode() == 'admin'
            ? true
            : false;
    }

    /**
     * Create Product array for Content API
     *
     * @param Product $product
     * @param int     $storeId
     *
     * @return array|false
     */
    public function getProductData(Product $product, $storeId = null)
    {
        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        if (!$storeId) {
            $storeScopes = $product->getStoreIds();
            $storeId = $this->request->getParam('store') ? : $storeScopes[0];
            if ($storeId) {
                $product->setStoreId($storeId);
            }
        }
        $this->_storeManager->setCurrentStore($storeId);

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
                'price'       => $price = ($product->getPrice()
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
                $pIds = $this->cpModel->getParentIdsByChild($product->getId());
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
            if ($image = $product->getImage()) {
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
