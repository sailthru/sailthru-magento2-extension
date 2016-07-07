<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Model\Product;
use Sailthru\MageSail\Helper\Api;

class ProductIntercept
{

	public function __construct(Api $sailthru){
		$this->sailthru = $sailthru;
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
            $data = array('url' => $product->getProductUrl(),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $product->getPrice(),
                'description' => urlencode($product->getDescription()),
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'images' => array(),
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
                    'weight'  => $product->getWeight()
                )
            );
            // Add product images
            if($product->getImage()) {
                $data['images']['full'] = array ("url" => $product->getImageUrl());
            }

            return $data;
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }
}