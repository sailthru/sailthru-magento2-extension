<?php

namespace Sailthru\MageSail\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use \Sailthru\MageSail\Plugin\ProductIntercept as SailthruIntercept;

class Cron
{
    const LOAD_COLLECTION_STEP = 500;
    const FLAG_NAME = 'catalog_product_sync_sailthru';

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
    private $_storeManager;

    /**
     * Cron constructor.
     * @param ProductCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param SailthruIntercept $sailthruIntercept
     * @param SailthruProduct $sailthruProduct
     * @param FlagManager $flagManager
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        SailthruIntercept $sailthruIntercept,
        SailthruProduct $sailthruProduct,
        FlagManager $flagManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->sailthruProduct  = $sailthruProduct;
        $this->sailthruIntercept = $sailthruIntercept;
        $this->_storeManager = $storeManager;
        $this->flagManager = $flagManager;
    }

    public function exportProducts()
    {

        $storeManagerDataList = $this->_storeManager->getStores();
        $storesCronStatus = [];
        $storesCronStatus[0] = (int)$this->sailthruProduct->isSyncProductCronEnable();
        foreach ($storeManagerDataList as $store) {
            $storesCronStatus[$store->getStoreId()] = (int)$this->sailthruProduct->isSyncProductCronEnable($store->getStoreId());
        }
        if(!in_array(1, $storesCronStatus)) {
            return false;
        }

        $collection = $this->collectionFactory->create();
        $collectionSize = $collection->getSize();
        $attemptsCount = $this->flagManager->getFlagData(self::FLAG_NAME);
        if (($attemptsCount === null) || ($attemptsCount <= 0)) {
            $attemptsCount = 0;
        }
        for ($i = $attemptsCount; $i < $collectionSize; $i += self::LOAD_COLLECTION_STEP) {
            $collection
                ->addAttributeToSelect('*')
                ->setOrder('entity_id', 'ASC')
                ->addFieldToFilter('entity_id', ['from' => $i, 'to' => $i + self::LOAD_COLLECTION_STEP]);;
            foreach ($collection as $product) {
                foreach ($product->getStoreIds() as $storeId) {
                    if ($storesCronStatus[$storeId]) {
                        $this->sailthruIntercept->sendRequest($product, $storeId);
                    }
                }

            }
            $this->flagManager->saveFlag(self::FLAG_NAME, $i + self::LOAD_COLLECTION_STEP);

        }
        $this->flagManager->deleteFlag(self::FLAG_NAME);
    }
}