<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigProduct;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\Api;

class ProductIntercept
{

	public function __construct(Api $sailthru, StoreManagerInterface $storeManager, ProductHelper $productHelper, ImageHelper $imageHelper, ConfigProduct $cpModel){
		$this->sailthru = $sailthru;
		$this->_storeManager = $storeManager;
		$this->productHelper = $productHelper;
        $this->imageHelper = $imageHelper;
        $this->cpModel = $cpModel;
	}

	public function afterAfterSave(Product $productModel, $productResult){
		$data = $this->getProductData($productResult);
		try {
	        $this->sailthru->client->_eventType = 'SaveProduct';
            $response = $this->sailthru->client->apiPost('content', $data);
        } catch(\Exception $e) {
            $this->sailthru->logger($e);
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
        
        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        // $storeScopes = $product->getStoreIds();
        // $storeId = $storeScopes ? $storeScopes[0] : $product->getStoreId();
        // if ($storeId) $product->setStoreId($storeId);
        // $this->_storeManager->setCurrentStore($storeId);
        $parents = $this->cpModel->getParentIdsByChild($product->getId());
        try {
            $data = [
                'url'   => $parents ? $this->getProductFragmentedUrl($product, $parents[0]) : $product->setStoreId($storeId)->getProductUrl(true),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $product->getPrice() * 100,
                'description' => strip_tags($product->getDescription()),
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'images' => array(),
                'inventory' => $product->getStockData()["qty"],
                // 'vars' => [
                    // 'options2' => $product->getOptions(),
                    // 'options3' => $product->getCustomOptions(),
                    // 'codes' => $product->getCustomAttributesCodesf(),
                    // 'sku' => $product->getSku(),
                    // 'storeId' => $storeId,
                    // 'typeId' => $product->getTypeId(),
                    // 'status' => $product->getStatus(),
                    // 'categoryId' => $product->getCategory(),
                    // 'categoryIds' => $product->getCategoryIds(),
                    // 'websiteIds' => $product->getWebsiteIds(),
                    // 'storeIds'  => $product->getStoreIds(),
                    // 'price' => $product->getPrice() * 100,
                    // 'groupPrice' => $product->getGroupPrice(),
                    // 'formatedPrice' => $product->getFormatedPrice(),
                    // 'calculatedFinalPrice' => $product->getCalculatedFinalPrice(),
                    // 'minimalPrice' => $product->getMinimalPrice(),
                    // 'specialPrice' => $product->getSpecialPrice(),
                    // 'specialFromDate' => $product->getSpecialFromDate(),
                    // 'specialToDate'  => $product->getSpecialToDate(),
                    // 'relatedProductIds' => $product->getRelatedProductIds(),
                    // 'upSellProductIds' => $product->getUpSellProductIds(),
                    // 'getCrossSellProductIds' => $product->getCrossSellProductIds(),
                    // 'isConfigurable'  => $product->canConfigure(),
                    // 'isSalable' => $product->isSalable(),
                    // 'isAvailable'  => $product->isAvailable(),
                    // 'isVirtual'  => $product->isVirtual(),
                    // 'isInStock'  => $product->isInStock(),
                    // 'weight'  => $product->getWeight(),
                    // 'isVisible' => $this->productHelper->canShow($product)
                    // 'product' => $product->toArray(),
                // ],
                'vars' => $this->_getProductAttributeValues($product),
            ];
            // Add product images
            if($image = $product->getImage()) {
                $data['images']['thumb'] = ["url" => $this->imageHelper->init($product, 'product_listing_thumbnail')->getUrl()];
                $data['images']['full'] = ['url'=> $this->getBaseImageUrl($product)];
            }
            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
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
            if ($this->attributeGate($label)){
                $value = $attribute->getFrontend()->getValue($product);
                if ($value and $label and $value != "No"){
                    $data[$label] = $value;
                }
            }
        }
        return $data;
    }

    private function attributeGate($attr){
        if ($attr == "thumbnail" or strpos($attr, "media") !== false or strpos($attr, "image") !== false) return false;
        return true;
    }
}