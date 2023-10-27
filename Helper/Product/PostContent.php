<?php

namespace Sailthru\MageSail\Helper\Product;

use Sailthru\MageSail\MageClient;
use Magento\Catalog\Model\Product as ProductModel;

class PostContent extends RequestAbstract
{
    /**
     * @var string
     */
    protected $eventType = 'SaveProduct';

    /**
     * To send single request to API.
     *
     * @param MageClient $client
     * @param array $data
     *
     * @return RequestAbstract
     */
    protected function executeRequest(MageClient $client, array $data)
    {
        $client->apiPost('content', $data);

        return $this;
    }

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
        return parent::validateProduct($product, $storeId)
            && (!empty($this->sailthruProduct->isVariant($product)) || !empty($this->productHelper->canShow($product)));
    }

    /**
     * Get Product data
     *
     * @param ProductModel $product
     * @param int $storeId
     *
     * @return array
     */
    public function getProductData(ProductModel $product, int $storeId = null)
    {
        $attributes = $this->sailthruProduct->getProductAttributeValuesForTagsVars($product);
        $categories = $this->sailthruProduct->getCategories($product);

        $isMaster = $product->getTypeId() == 'configurable';
        $isVariant = $this->sailthruProduct->isVariant($product);
        $data = [
            'url'         => $this->sailthruProduct->getProductUrl($product, $storeId),
            'keys'        => [
                'sku' => count($product->getStoreIds()) > 1
                    ? $storeId . '-' . $product->getSku()
                    : $product->getSku()
            ],
            'title'       => htmlspecialchars($product->getName() ?? ''),
            'spider'      => 0,
            'price'       => ($product->getPrice()
                    ? $product->getPrice()
                    : $product->getPriceInfo()->getPrice('final_price')->getValue()) * 100,
            'description' => $product->getDescription() ? strip_tags($product->getDescription()) : '',
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
                    'formatedPrice'        => $product->getFormattedPrice(),
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
    }
}
