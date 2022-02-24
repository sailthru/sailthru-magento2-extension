<?php

namespace Sailthru\MageSail\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Product as ProductHelper;

class DeleteContent implements ObserverInterface
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @param ClientManager $clientManager
     * @param ProductHelper $productHelper
     */
    public function __construct(
        ClientManager $clientManager,
        ProductHelper $productHelper
    ) {
        $this->clientManager = $clientManager;
        $this->productHelper = $productHelper;
    }

    /**
     * Remove product from Sailthru
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        try {
            $this->productHelper->deleteContent($product);
        } catch (\Throwable $t) {
            $client = $this->clientManager->getClient($product->getStoreId());
            $client->logger(__('Error of remove product data from Sailthru'));
            $client->logger($t->getMessage());
        }
    }
}
