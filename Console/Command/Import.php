<?php


namespace Sailthru\MageSail\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Plugin\ProductIntercept;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends Command
{

    const STORE_ARGUMENT = "<storeID>";

    const EVENT_NAME = "cli_product_sync";

    /** @var ClientManager  */
    protected $clientManager;

    /** @var ProductIntercept  */
    protected $productIntercept;

    /** @var Collection  */
    protected $productCollection;

    /** @var Emulation  */
    protected $emulation;

    /** @var State  */
    private $state;

    /** @var StoreManagerInterface  */
    private $storeManager;

    /** @var ProductRepositoryInterface  */
    private $productRepo;

    /** @var SearchCriteriaBuilder  */
    private $criteriaBuilder;

    public function __construct(
        ClientManager $clientManager,
        ProductIntercept $productIntercept,
        Collection $productCollection,
        Emulation $emulation,
        State $state,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepo,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        parent::__construct();
        $this->clientManager = $clientManager;
        $this->productIntercept = $productIntercept;
        $this->productCollection = $productCollection;
        $this->emulation = $emulation;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->productRepo = $productRepo;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $storeId = $input->getArgument(self::STORE_ARGUMENT);
        $this->productCollection->setStoreId($storeId);
        $this->state->setAreaCode(Area::AREA_FRONTEND);

        $this->productCollection
            ->addAttributeToSelect("*")
            ->addStoreFilter($storeId)
            ->joinField('qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->setPageSize(75)
            ->load();

        $storeName = $this->storeManager->getStore($storeId)->getName();
        $output->writeln("Checking {$this->productCollection->getSize()} products to import for Store #$storeId $storeName");

        $sailClient = $this->clientManager->getClient(true, $storeId);
        $sailClient->_eventType = $this::EVENT_NAME;

        $actionId = time();
        $checkedProducts = 0;
        $syncedProducts = 0;
        $skippedProducts = [];
        $failedProducts = [];
        $page = 1;
        $startTime = microtime(true);

        do {
            $this->productCollection->setCurPage($page++)->load();
            /** @var Product $product */
            foreach ($this->productCollection->getItems() as $product) {
                if ($checkedProducts != 0 && $checkedProducts % 100 == 0) {
                    $output->writeln("\nChecked $checkedProducts products...");
                }

                try {
                    $status = $product->getStatus();
                    if ($status == Status::STATUS_ENABLED and $payload = $this->productIntercept->getProductData($product, $storeId)) {
                        $payload['integration_action'] = $this::EVENT_NAME;
                        $payload['integration_action_id'] = $actionId;
                        $sailClient->apiPost('content', $payload);
                        $syncedProducts++;
                    } else {
                        $skippedProducts[] = $product;
                    }

                } catch (\Sailthru_Client_Exception $e) {
                    $failedProducts[] = $product;
                } catch (\Exception $e) {
                    $output->writeln("Error on SKU {$product->getSku()}: {$e->getMessage()}");
                } finally {
                    $checkedProducts++;
                }
            }

            $this->productCollection->clear();

        } while ($page <= $this->productCollection->getLastPageNumber());

        $endTime = microtime(true);
        $time = $endTime - $startTime;

        if ($skippedProducts) {
            $productString = $this->printableProducts($skippedProducts);
            $output->writeln("\n*****\nSkipped Products\n\n$productString\n*****");
        }

        if ($failedProducts) {
            $productString = $this->printableProducts($failedProducts);
            $output->writeln("\n*****\nFailed Products\n\n$productString\n*****");
        }

        $output->writeln("\n\n******\nResults\n\nSynced: {$syncedProducts} products\nTime: {$time} seconds\n*****");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("sailthru:import:products");
        $this->setDescription("Starts Product Catalog import into Sailthru for a given store");
        $this->setDefinition([
            new InputArgument(self::STORE_ARGUMENT, InputArgument::REQUIRED, "ID of store to sync"),
        ]);
        parent::configure();
    }

    /**
     * @param Product[] $products
     *
     * @return string
     */
    private function printableProducts($products)
    {
        $ids = array_map( function(Product $product) {
            return $product->getSku();
        }, $products);

        return implode(",", $ids);
    }
}