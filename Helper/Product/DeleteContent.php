<?php

namespace Sailthru\MageSail\Helper\Product;

use Sailthru\MageSail\MageClient;
use Magento\Catalog\Model\Product as ProductModel;

class DeleteContent extends RequestAbstract
{
    /**
     * @var string
     */
    protected $eventType = 'DeleteProduct';

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
        $client->apiDelete('content', $data);

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
    public function validationProductData(ProductModel $product, int $storeId = null)
    {
        return $this->sailthruProduct->shouldRemoveProductDataInSailthru($storeId)
            && parent::validationProductData($product, $storeId);
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
        return [
            'id' => count($product->getStoreIds()) > 1
                ? $storeId . '-' . $product->getSku()
                : $product->getSku(),
            'key' => 'sku',
        ];
    }
}
