<?php

namespace Sailthru\MageSail\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeDeleteContent implements ObserverInterface
{
    /**
     * Get all store ids and save in product data
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $product->getStoreIds();
    }
}
