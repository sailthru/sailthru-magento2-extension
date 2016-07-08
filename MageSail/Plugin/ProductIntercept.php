<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\Api;

class ProductIntercept
{

	public function __construct(Api $sailthru, StoreManagerInterface $storeManager, \Magento\Catalog\Helper\Product $productHelper){
		$this->sailthru = $sailthru;
		$this->_storeManager = $storeManager;
		$this->productHelper = $productHelper;
	}

	public function afterAfterSave(Product $productModel, $productResult){
		$this->sailthru->logger('Updating a product!');
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
        try {
            $data = array(
            	'url' => $this->productHelper->getProductUrl($product),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $product->getPrice() * 10,
                'description' => urlencode($product->getDescription()),
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'images' => array(),
                'inventory' => $product->getStockData()["qty"],
                'vars' => array('sku' => $product->getSku(),
                    'storeId' => '',
                    'typeId' => $product->getTypeId(),
                    'status' => $product->getStatus(),
                    'categoryId' => $product->getCategoryId(),
                    'categoryIds' => $product->getCategoryIds(),
                    'websiteIds' => $product->getWebsiteIds(),
                    'storeIds'  => $product->getStoreIds(),
                    //'attributes' => $product->getAttributes(),
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
                    // 'isSuperGroup' => $product->isSuperGroup(),
                    // 'isGrouped'   => $product->isGrouped(),
                    'isConfigurable'  => $product->canConfigure(),
                    // 'isSuper' => $product->isSuper(),
                    'isSalable' => $product->isSalable(),
                    'isAvailable'  => $product->isAvailable(),
                    'isVirtual'  => $product->isVirtual(),
                    // 'isRecurring' => $product->isRecurring(),
                    'isInStock'  => $product->isInStock(),
                    'weight'  => $product->getWeight(),
                    'isVisible' => $this->productHelper->canShow($product)
                )
            );
            // Add product images
            if($image = $product->getImage()) {
                $data['images']['full'] = array ("url" => $this->productHelper->getImageUrl($product));
                $data['images']['small'] = array("url" => $this->productHelper->getSmallImageUrl($product));
            }

            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

	/**
	 * Get Absolute Media Path. Based on https://mage2.pro/t/topic/276
	 * @param string $relativeMediaPath
	 * @return string
	 */
	public function getAbsoluteURI($relativeURI) {		
	    $base = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
	    $uri = str_replace("\\", "", $relativeURI);
	    return "{$base}product{$uri}";

	}


}