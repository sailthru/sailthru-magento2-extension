<?php

namespace Sailthru\MageSail\Helper;

class Product extends AbstractHelper
{

    const XML_CONTENT_INTERCEPT        = "magesail_content/intercept/enable_intercept";
    const XML_CONTENT_SEND_MASTER      = "magesail_content/intercept/send_master";
    const XML_CONTENT_SEND_VARIANTS    = "magesail_content/intercept/send_variants";
    const XML_CONTENT_USE_KEYWORDS     = "magesail_content/tags/use_seo";
    const XML_CONTENT_USE_CATEGORIES   = "magesail_content/tags/use_categories";
    const XML_CONTENT_USE_ATTRIBUTES   = "magesail_content/tags/use_attributes";

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

    /**
     * Is the Product-saving interceptor enabled
     * @return bool
     */
    public function isProductInterceptOn()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_INTERCEPT));
    }

    /**
     * Is the product-saving interceptor sync'ing master products to Sailthru
     * @return bool
     */
    public function canSyncMasterProducts()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_SEND_MASTER));
    }

    /**
     * Is the product-saving interceptor sync'ing variant products to Sailthru
     * @return bool
     */
    public function canSyncVariantProducts()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_SEND_VARIANTS));
    }

    /**
     * Is Sailthru building tags from meta keywords
     * @return bool
     */
    public function tagsUseKeywords()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_KEYWORDS));
    }

    /**
     * Is Sailthru building tags from product attributes
     * @return bool
     */
    public function tagsUseAttributes()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_ATTRIBUTES));
    }

    /**
     * Is Sailthru building tags from product categories
     * @return bool
     */
    public function tagsUseCategories()
    {
        return boolval($this->getSettingsVal(self::XML_CONTENT_USE_CATEGORIES));
    }

    /**
     * Get string-concatenated tags for a product
     * @param \Magento\Catalog\Model\Product $product
     * @param array|null $attributes
     * @param string[]|null $categories
     *
     * @return string
     */
    public function getTags($product, $attributes = null, $categories = null)
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
                        $attribute_str .= (($value == "Yes" or $value == "Enabled") ? $key : $value) . ",";
                    }
                }
                $tags .= $attribute_str;
            }
        } catch (\Exception $e) {
            $this->logger($e);
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
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string[]
     */
    public function getCategories(\Magento\Catalog\Model\Product $product)
    {
        $collection = $product->getCategoryCollection();
        $items = $collection->addAttributeToSelect('name')->getItems();
        $categories = [];
        foreach ($items as $item) {
            $categories[] = $item->getName();
        }
        return $categories;
    }
}
