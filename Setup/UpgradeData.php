<?php

/**
 * Add sailthru_processed flag to orders for backfilling.
 * based on https://magento.stackexchange.com/questions/107280/magento2-adding-custom-order-attribute
 */

namespace Sailthru\MageSail\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Sailthru\MageSail\Logger;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * Quote setup factory
     *
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    private $logger;

    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param Logger $logger
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        Logger $logger
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), "1.0.3", ">")) {

            $this->logger->info("Trying to add new boolean!");
            $options = [
                'type' => 'boolean',
                'label' => 'Processed Sailthru',
                'required' => false,
                'visible' => true,
                'sort_order' => 1000,
                'position' => 1000,
                'system' => 0,
            ];
            $salesSetup->addAttribute('order', 'sailthru_processed', $options);
        }
    }
}