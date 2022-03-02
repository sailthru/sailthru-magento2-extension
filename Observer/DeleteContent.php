<?php

namespace Sailthru\MageSail\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Product\DeleteContent as DeleteContentHelper;
use Magento\Store\Model\StoreManagerInterface;

class DeleteContent implements ObserverInterface
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var DeleteContentHelper
     */
    protected $deleteContentHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ClientManager $clientManager
     * @param DeleteContentHelper $deleteContentHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ClientManager $clientManager,
        DeleteContentHelper $deleteContentHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->clientManager = $clientManager;
        $this->deleteContentHelper = $deleteContentHelper;
        $this->storeManager = $storeManager;
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
            if ($this->storeManager->getStore($product->getStoreId())->getCode() == 'admin') {
                $this->deleteContentHelper->sendMultipleRequests($product);
            } else {
                $this->deleteContentHelper->sendRequest($product);
            }
        } catch (\Throwable $t) {
            $client = $this->clientManager->getClient($product->getStoreId());
            $client->logger(__('Error of remove product data from Sailthru'));
            $client->logger($t->getMessage());
        }
    }
}
