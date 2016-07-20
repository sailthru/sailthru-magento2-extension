<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigProduct;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\Api;

class ProductIntercept
{

	public function __construct(Api $sailthru, StoreManagerInterface $storeManager, \Magento\Catalog\Helper\Product $productHelper, ConfigProduct $cpModel){
		$this->sailthru = $sailthru;
		$this->_storeManager = $storeManager;
		$this->productHelper = $productHelper;
        $this->cpModel = $cpModel;
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
        
        // scope fix for intercept launched from backoffice, which causes admin URLs for products
        $storeScopes = $product->getStoreIds();
        $storeId = $storeScopes ? $storeScopes[0] : $product->getStoreId();
        if ($storeId) $product->setStoreId($storeId);
        $this->_storeManager->setCurrentStore($storeId);
        $parents = $this->cpModel->getParentIdsByChild($product->getId());
        try {
            $data = [
                'url'   => $parents ? $this->productHelper->getProductUrl($parents[0]) : $product->setStoreId($storeId)->getProductUrl(true),
                'title' => htmlspecialchars($product->getName()),
                //'date' => '',
                'spider' => 1,
                'price' => $product->getPrice() * 100,
                'description' => $product->getDescription(),
                'tags' => htmlspecialchars($product->getMetaKeyword()),
                'images' => array(),
                'inventory' => $product->getStockData()["qty"],
                'vars' => [
                    'sku' => $product->getSku(),
                    'storeId' => $storeId,
                    'typeId' => $product->getTypeId(),
                    'status' => $product->getStatus(),
                    'categoryId' => $product->getCategoryId(),
                    'categoryIds' => $product->getCategoryIds(),
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
                ],
            ];
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


}