<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Product\PostContent as PostContentHelper;

class ProductIntercept
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var PostContentHelper
     */
    protected $postContentHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ClientManager $clientManager
     * @param PostContentHelper $postContentHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ClientManager $clientManager,
        PostContentHelper $postContentHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->clientManager = $clientManager;
        $this->postContentHelper = $postContentHelper;
        $this->storeManager = $storeManager;
    }

    public function afterAfterSave(Product $subject, Product $result)
    {
        try {
            if ($this->storeManager->getStore($result->getStoreId())->getCode() == Area::AREA_ADMINHTML) {
                $this->postContentHelper->sendMultipleRequests($result);
            } else {
                $this->postContentHelper->sendRequest($result);
            }
        } catch (\Throwable $t) {
            $client = $this->clientManager->getClient($result->getStoreId());
            $client->logger(__('Error of send product data to Sailthru'));
            $client->logger($t->getMessage());
        }

        return $result;
    }
}
