<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Configurable;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\Api;


class ProductIntercept
{
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

    public function __construct(Api $sailthru, StoreManagerInterface $storeManager, ProductHelper $productHelper, ImageHelper $imageHelper, Configurable $cpModel){
        $this->sailthru = $sailthru;
        $this->_storeManager = $storeManager;
        $this->productHelper = $productHelper;
        $this->imageHelper = $imageHelper;
        $this->cpModel = $cpModel;
    }

    public function afterAfterSave(Product $productModel, $productResult){
        if ($this->sailthru->isProductInterceptOn()){
            $data = $this->getProductData($productResult);
            if ($data){
                try {
                    $this->sailthru->client->_eventType = 'SaveProduct';
                    $response = $this->sailthru->client->apiPost('content', $data);
                } catch(\Exception $e) {
                    $this->sailthru->logger('ProductData Error');
                    $this->sailthru->logger($e->getMessage());
                }
            }
        }
        return $productResult;
    }

    /**
     * Create Product array for Content API
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getProductData($product)
    {
        $productType = $product->getTypeId();
        $isMaster = ($productType == 'configurable');
        $updateMaster = $this->sailthru->canSyncMasterProducts();
        if ($isMaster and !$updateMaster){
            return false;
        }

        $isSimple = ($productType == 'simple');
        $parents = $this->cpModel->getParentIdsByChild($product->getId());
        $isVariant = ($isSimple and $parents);
        $updateVariants = $this->sailthru->canSyncVariantProducts();
        $this->sailthru->logger($updateVariants);
        if ($isVariant and !$updateVariants){
            return false;
        }

        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        $storeScopes = $product->getStoreIds();
        $storeId = $storeScopes ? $storeScopes[0] : $product->getStoreId();
        if ($storeId) $product->setStoreId($storeId);
        $this->_storeManager->setCurrentStore($storeId);


        $attributes = $this->_getProductAttributeValues($product);
        $categories = $this->getCategories($product);

        try {
            $data = [
                'url'   => $isVariant ? $this->getProductFragmentedUrl($product, $parents[0]) : $product->setStoreId($storeId)->getProductUrl(true),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $price = ($product->getPrice() ? $product->getPrice() : $product->getPriceInfo()->getPrice('final_price')->getValue()) * 100,
                'description' => strip_tags($product->getDescription()),
                'tags' => $this->getTags($product, $attributes, $categories),
                'images' => array(),
                'vars' => [
                    'sku' => $product->getSku(),
                    'storeId' => $product->getStoreId(),
                    'typeId' => $product->getTypeId(),
                    'status' => $product->getStatus(),
                    'categories' => $this->getCategories($product),
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
                    'getCrossSellProductIds' => $product->getCrossSellProductIds(),
                    'isConfigurable'  => $product->canConfigure(),
                    'isSalable' => $product->isSalable(),
                    'isAvailable'  => $product->isAvailable(),
                    'isVirtual'  => $product->isVirtual(),
                    'isInStock'  => $product->isInStock(),
                    'weight'  => $product->getWeight(),
                    'isVisible' => $this->productHelper->canShow($product)
                ] + $attributes,
            ];

            if($isVariant) $data['inventory'] = $product->getStockData()["qty"];

            // Add product images
            if($image = $product->getImage()) {
                $data['images']['thumb'] = ["url" => $this->imageHelper->init($product, 'product_listing_thumbnail')->getUrl()];
                $data['images']['full'] = ['url'=> $this->getBaseImageUrl($product)];
            }
            if($parents and sizeof($parents) == 1){
                $data['vars']['parentID'] = $parents[0];
            }

            return $data;

        } catch(Exception $e) {
            throw $e;
        }
    }

    public function getProductFragmentedUrl($product, $parent){
        $parentUrl = $this->productHelper->getProductUrl($parent);
        $pSku = $product->getSku();
        return "{$parentUrl}#{$pSku}";
    }


    // Magento 2 getImage seems to add a strange slash, therefore this.
    public function getBaseImageUrl($product){
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
    }

    private function _getProductAttributeValues($product){
        $setId = $product->getAttributeSetId();
        $attributeSet = $product->getAttributes();
        $data = [];
        foreach ($attributeSet as $attribute) {
            $label = $attribute->getName();
            if (!in_array($label, self::$unusedVarKeys)){
                $value = $attribute->getFrontend()->getValue($product);
                if ($value and $label and $value != "No"){
                    $data[$label] = $value;
                }
            }
        }
        return $data;
    }


    public function getTags($product, $attributes, $categories){
        $tags = '';
        if ($this->sailthru->tagsUseKeywords()) {
          $keywords = htmlspecialchars($product->getData('meta_keyword'));  
          $tags .= "$keywords,";
        }
        if ($this->sailthru->tagsUseAttributes()){ 
            foreach ($attributes as $key => $value) {
                if (!is_numeric($value)){
                    $tags .= (($value == "Yes" or $value == "Enabled") ? $key : $value) . ",";
                }
            }
        }
        if ($this->sailthru->tagsUseCategories()){
            $tags .= implode(",", $categories);
        }
        else {
            $this->sailthru->logger('skipping tags');
        }

        return $tags;
    }

    public function getCategories($product){
        $collection = $product->getCategoryCollection();
        $items = $collection->addAttributeToSelect('name')->getItems();
        $categories = [];
        foreach ($items as $item) {
            $categories[] = $item->getName();
        }
        return $categories;
    }

}