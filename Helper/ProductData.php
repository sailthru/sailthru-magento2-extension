<?php

namespace Sailthru\MageSail\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Zend\View\Helper\Url;

class ProductData extends AbstractHelper
{

    const XML_CONTENT_INTERCEPT        = "magesail_content/intercept/enable_intercept";
    const XML_CONTENT_SEND_MASTER      = "magesail_content/intercept/send_master";
    const XML_CONTENT_SEND_VARIANTS    = "magesail_content/intercept/send_variants";
    const XML_CONTENT_USE_KEYWORDS     = "magesail_content/tags/use_seo";
    const XML_CONTENT_USE_CATEGORIES   = "magesail_content/tags/use_categories";
    const XML_CONTENT_USE_ATTRIBUTES   = "magesail_content/tags/use_attributes";
    const XML_CONTENT_ATTRIBUTES_LIST  = "magesail_content/tags/usable_attributes";

    public static $unusedVarKeys = [
        'status',
        'row_id',
        'type_id',
        'attribute_set_id',
        'media_gallery',
        'thumbnail',
        'shipment_type',
        'url_key',
        'price_view',
        'msrp_display_actual_price_type',
        'page_layout',
        'options_container',
        'custom_design',
        'custom_layout',
        'gift_message_available',
        'category_ids',
        'image',
        'small_image',
        'visibility',
        'relatedProductIds',
        'upSellProductIds',
        'description',
        'meta_keyword',
        'name',
        'created_at',
        'updated_at',
        'tax_class_id',
        'quantity_and_stock_status',
        'sku'
    ];
    
    /** @var ConfigurableProduct */
    private $configurableProduct;

    /** @var ProductRepositoryInterface */
    private $productRepo;

    private $imageBuilder;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ConfigurableProduct $configurableProduct,
        ProductRepositoryInterface $productRepo,
        ImageBuilder $imageBuilder
    ) {
        parent::__construct($context, $storeManager, $logger, $templateModel, $templateConfig, $objectManager);
        $this->configurableProduct = $configurableProduct;
        $this->productRepo = $productRepo;
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * Is the Product-saving interceptor enabled
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function isProductInterceptOn($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_INTERCEPT, $storeId));
    }

    /**
     * Is the product-saving interceptor sync'ing master products to Sailthru
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function canSyncMasterProducts($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_SEND_MASTER, $storeId));
    }

    /**
     * Is the product-saving interceptor sync'ing variant products to Sailthru
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function canSyncVariantProducts($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_SEND_VARIANTS, $storeId));
    }

    /**
     * Is Sailthru building tags from meta keywords
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function tagsUseKeywords($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_KEYWORDS, $storeId));
    }

    /**
     * Is Sailthru building tags from product attributes
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function tagsUseAttributes($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_ATTRIBUTES, $storeId));
    }

    public function getUsableAttributes($storeId = null)
    {
        return explode(",", $this->getSettingsVal(self::XML_CONTENT_ATTRIBUTES_LIST, $storeId));
    }

    /**
     * Is Sailthru building tags from product categories
     * @param string|null $storeId
     * 
     * @return bool
     */
    public function tagsUseCategories($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_CATEGORIES, $storeId));
    }

    /**
     * Get string-concatenated tags for a product
     * @param Product $product
     * @param array|null $attributes
     * @param string[]|null $categories
     *
     * @return string
     */
    public function getTags(Product $product, $attributes = null, $categories = null)
    {
        $tags = '';
        if ($this->tagsUseKeywords()) {
            $keywords = htmlspecialchars($product->getData('meta_keyword'));
            $tags .= "$keywords,";
        }
        if ($this->tagsUseCategories()) {
            if ($categories === null) {
                $categories = $this->getCategories($product);
            }
            $tags .= implode(",", $categories);
        }
        try {
            $attribute_str = '';
            if ($this->tagsUseAttributes()) {
                if ($attributes === null) {
                    $attributes = $this->getProductAttributeValues($product);
                }
                foreach ($attributes as $key => $value) {
                    if (!is_numeric($value)) {
                        $attribute_str .= (($value == "Yes" || $value == "Enabled") ? $key : $value) . ",";
                    }
                }

                if ($attribute_str && $tags) {
                    $tags .= ",";
                }
                $tags .= $attribute_str;
            }
        } catch (\Exception $e) {
            $this->logger->err($e);
        }
        return $tags;
    }

    /**
     * Build product attributes for product vars and tags
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getProductAttributeValues(\Magento\Catalog\Model\Product $product)
    {
        $setId = $product->getAttributeSetId();
        $attributeSet = $product->getAttributes();
        $data = [];
        foreach ($attributeSet as $attribute) {
            $label = $attribute->getName();
            if (!in_array($label, self::$unusedVarKeys)) {
                $value = $attribute->getFrontend()->getValue($product);
                if ($value and $label and $value != "No" and $value != " ") {
                    $data[$label] = $value;
                }
            }
        }
        return $data;
    }

    /**
     * Get all the categories for a given product
     * @param Product $product
     *
     * @return string[]
     */
    public function getCategories(Product $product)
    {
        $collection = $product->getCategoryCollection();
        $items = $collection->addAttributeToSelect('name')->getItems();
        $categories = [];
        foreach ($items as $item) {
            $categories[] = $item->getName();
        }
        return $categories;
    }

    /**
     * Checks whether a product is simple and has parents (a product "variant"). Can optionally return the parent IDs.
     * @param Product $product
     * @param bool    $returnParentIds
     *
     * @return int[]|bool
     */
    public function isVariant(Product $product, $returnParentIds = false)
    {
        $isSimple = $product->getTypeId() == 'simple';
        $parents = $this->configurableProduct->getParentIdsByChild($product->getId());
        if ($isSimple && $parents) {
            return $returnParentIds
                ? $parents
                : true;
        }
        return false;
    }

    /**
     * Generates interceptor-safe product url for correct store.
     * For variants, will return the configurable product's url with
     * #<variant_sku> anchored at the end.
     *
     * @param  Product $product
     * @param  int $storeId
     *
     * @return string
     */
    public function getProductUrl(Product $product, $storeId = null)
    {
        if ($storeId) {
            $product->setStoreId($storeId);
        }

        return ($parents = $this->isVariant($product, true))
            ? $this->buildVariantUrl($product, $parents[0])
            : $this->buildSafeUrl($product);
    }

    /**
     * Generate a product URL by a SKU
     * @param string   $sku
     * @param int|null $storeId
     *
     * @return string
     */
    public function getProductUrlBySku($sku, $storeId = null)
    {
        /** @var Product $product */
        $product = $this->productRepo->get($sku);
        return $this->getProductUrl($product, $storeId);
    }

    /**
     * Generates a Sailthru-safe URL for a variant product
     * @param Product $product
     * @param int     $parentId
     *
     * @return string
     */
    protected function buildVariantUrl(Product $product, $parentId)
    {
        /** @var Product $parent */
        $parent = $this->productRepo->getById($parentId);
        $parent->setStoreId($product->getStoreId());
        $parentUrl = $this->buildSafeUrl($parent);
        $pSku = $product->getSku();
        return "{$parentUrl}#{$pSku}";
    }

    /**
     * Generate a Sailthru-safe direct URL for a product
     * Shouldn't be used for variants.
     * @param Product $product
     *
     * @return string
     */
    protected function buildSafeUrl(Product $product)
    {
        $product->getProductUrl(false); // generates the URL key
        /** @var Store $store */
        $store = $this->storeManager->getStore($product->getStoreId());
        return $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true) . $product->getRequestPath();
    }

    // Magento 2 getImage seems to add a strange slash, therefore this.
    public function getBaseImageUrl(Product $product)
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($product->getStoreId());
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product' . $product->getImage();
    }

    public function retrieveThumbnail($product)
    {
        $imageWidth = 200;
        $imageHeight = 200;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var Image $imageHelper */
        $imageHelper  = $objectManager->get('\Magento\Catalog\Helper\Image');
        $image_url = $imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getFile())->resize($imageWidth, $imageHeight)->getUrl();
        return $image_url;
    }
}
