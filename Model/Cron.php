<?php

namespace Sailthru\MageSail\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use \Sailthru\MageSail\Plugin\ProductIntercept as SailthruIntercept;
use Sailthru\MageSail\Logger;

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
     * @var SailthruTemplates
     */
    protected $sailthruTemplates;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /** @var Logger */
    protected $logger;

    /**
     * Cron constructor.
     *
     * @param ProductCollectionFactory $collectionFactory
     * @param StoreManagerInterface    $storeManager
     * @param SailthruIntercept        $sailthruIntercept
     * @param SailthruProduct          $sailthruProduct
     * @param SailthruTemplates        $sailthruTemplates
     * @param FlagManager              $flagManager
     * @param Logger                   $logger
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        SailthruIntercept $sailthruIntercept,
        SailthruProduct $sailthruProduct,
        SailthruTemplates $sailthruTemplates,
        FlagManager $flagManager,
        Logger $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->sailthruProduct = $sailthruProduct;
        $this->sailthruIntercept = $sailthruIntercept;
        $this->sailthruTemplates = $sailthruTemplates;
        $this->storeManager = $storeManager;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
    }

    /**
     * Export all products to the Sailthru
     *
     * @return bool
     */
    public function exportProducts()
    {
        $shouldRunFlag = (int)$this->sailthruProduct->isProductScheduledSyncEnabled();
        $storeManagerDataList = $this->storeManager->getStores();
        $storesCronStatus = [
            \Magento\Store\Model\Store::DEFAULT_STORE_ID => $shouldRunFlag
        ];

        foreach ($storeManagerDataList as $store) {
            $shouldRunPerStoreFlag = (int)$this->sailthruProduct->isProductScheduledSyncEnabled($store->getStoreId());
            $storesCronStatus[$store->getStoreId()] = $shouldRunPerStoreFlag;
            if ($shouldRunPerStoreFlag) {
                $shouldRunFlag = $shouldRunPerStoreFlag;
            }
        }

        if (!$shouldRunFlag) {

            return false;
        }

        try {
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
        } catch (\Exception $e) {
            $this->logger->err($e);

            return false;
        }
    }

    /**
     * Add Sailthru templates to cache
     */
    public function syncSailthruTemplates()
    {
        try {
            $storeIds = array_merge([null], array_keys($this->storeManager->getStores()));
            foreach ($storeIds as $storeId) {
                $this->sailthruTemplates->getTemplatesByStoreId($storeId);
            }
        } catch (\Exception $e) {
            $this->logger->err('Cron Job sailthru_sync_templates - ' . $e->getMessage());
        }

        return $this;
    }
}