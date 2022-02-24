<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Model\Product;
use Sailthru\MageSail\Helper\Product as ProductHelper;

class ProductIntercept
{
    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @param ProductHelper $productHelper
     */
    public function __construct(
        ProductHelper $productHelper
    ) {
        $this->productHelper = $productHelper;
    }

    public function afterAfterSave(Product $subject, Product $result)
    {
        try {
            $this->productHelper->postContent($result);
        } catch (\Throwable $t) {
            $client = $this->clientManager->getClient($result->getStoreId());
            $client->logger(__('Error of send product data to Sailthru'));
            $client->logger($t->getMessage());
        }

        return $result;
    }
}
