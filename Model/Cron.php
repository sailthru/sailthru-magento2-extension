<?php

namespace Sailthru\MageSail\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use \Sailthru\MageSail\Plugin\ProductIntercept as SailthruIntercept;

class Cron
{
    const BATCH_SIZE          = 750;
    const FLAG_LAST_ENTITY_ID = 'sailthru_catalog_product_sync_last_entity_id';

    /**
     * @var FlagManager
     */
    private $flagManager;
    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SailthruProduct
     */
    private $sailthruProduct;

    /**
     * @var SailthruIntercept
     */
    private $sailthruIntercept;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Cron constructor.
     *
     * @param ProductCollectionFactory $collectionFactory
     * @param StoreManagerInterface    $storeManager
     * @param SailthruIntercept        $sailthruIntercept
     * @param SailthruProduct          $sailthruProduct
     * @param FlagManager              $flagManager
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        SailthruIntercept $sailthruIntercept,
        SailthruProduct $sailthruProduct,
        FlagManager $flagManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->sailthruProduct = $sailthruProduct;
        $this->sailthruIntercept = $sailthruIntercept;
        $this->storeManager = $storeManager;
        $this->flagManager = $flagManager;
    }

    /**
     * @return bool
     */
    public function exportProducts()
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $storesCronStatus = [];
        $storesCronStatus[0] = (int)$this->sailthruProduct->isProductScheduledSyncEnabled();
        foreach ($storeManagerDataList as $store) {
            $storesCronStatus[$store->getStoreId()] = (int)$this->sailthruProduct->isProductScheduledSyncEnabled($store->getStoreId());
        }

        if (empty($storesCronStatus)) {

            return false;
        }

        $collection = $this->collectionFactory->create();
        $collectionSize = $collection->getSize();
        $collectionLastEntityId = $this->flagManager->getFlagData(self::FLAG_LAST_ENTITY_ID);
        if (!$collectionLastEntityId) {
            $collectionLastEntityId = 0;
        }

        for ($i = 0; $i <= $collectionSize; $i += self::BATCH_SIZE) {
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->getSelect()
                ->order('entity_id ' . \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
                ->limit(self::BATCH_SIZE);

            $collection->getSelect()->where('entity_id > ?', $collectionLastEntityId);

            foreach ($collection as $product) {
                foreach ($product->getStoreIds() as $storeId) {
                    if (!empty($storesCronStatus[$storeId])) {
                        $this->sailthruIntercept->sendRequest($product, $storeId);
                    }
                }
            }

            $collectionLastEntityId = $collection->getLastItem()->getId();
            $this->flagManager->saveFlag(self::FLAG_LAST_ENTITY_ID, $collectionLastEntityId);
            unset($collection);
        }

        $this->flagManager->deleteFlag(self::FLAG_LAST_ENTITY_ID);

        return true;
    }
}