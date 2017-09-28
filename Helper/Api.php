<?php

/**
 * Deprecated
 */

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Cookie\Hid;
use Sailthru\MageSail\Logger;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    // Source models
    const SOURCE_MODEL_VALIDATION_MSG  = "Please Enter Valid Sailthru Credentials";

    // Settings main
    const XML_API_KEY                  = "magesail_config/service/api_key";
    const XML_API_SECRET               = "magesail_config/service/secret_key";
    const API_SUCCESS_MESSAGE          = "Successfully Validated!";

    // Settings lists
    const XML_ONREGISTER_LIST_ENABLED  = "magesail_lists/lists/enable_signup_list";
    const XML_ONREGISTER_LIST_VALUE    = "magesail_lists/lists/signup_list";
    const XML_NEWSLETTER_LIST_ENABLED  = "magesail_lists/lists/enable_newsletter";
    const XML_NEWSLETTER_LIST_VALUE    = "magesail_lists/lists/newsletter_list";

    // Settings transactionals
    const XML_ABANDONED_CART_ENABLED   = "magesail_send/abandoned_cart/enabled";
    const XML_ABANDONED_CART_TEMPLATE  = "magesail_send/abandoned_cart/template";
    const XML_ABANDONED_CART_TIME      = "magesail_send/abandoned_cart/delay_time";
    const XML_ABANDONED_CART_ANONYMOUS = "magesail_send/abandoned_cart/anonymous_carts";
    const XML_TRANSACTIONALS_ENABLED   = "magesail_send/transactionals/send_through_sailthru";
    const XML_TRANSACTIONALS_SENDER    = "magesail_send/transactionals/from_sender";
    const XML_ORDER_ENABLED            = "magesail_send/transactionals/purchase_enabled";
    const XML_ORDER_TEMPLATE           = "magesail_send/transactionals/purchase_template";

    // Content
    const XML_CONTENT_INTERCEPT        = "magesail_content/intercept/enable_intercept";
    const XML_CONTENT_SEND_MASTER      = "magesail_content/intercept/send_master";
    const XML_CONTENT_SEND_VARIANTS    = "magesail_content/intercept/send_variants";
    const XML_CONTENT_USE_KEYWORDS     = "magesail_content/tags/use_seo";
    const XML_CONTENT_USE_CATEGORIES   = "magesail_content/tags/use_categories";
    const XML_CONTENT_USE_ATTRIBUTES   = "magesail_content/tags/use_attributes";

    const UNKNOWN_TEMPLATE_ERROR_CODE = 14;

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
    public $client;
    public $hid;
    public $logger;
    public $storeManager;
    protected $_apiKey;
    protected $_apiSecret;
    private $sailthruTemplates = [];

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    public function __construct(
        Context $context,
        Hid $hid,
        Logger $logger,
        StoreManager $storeManager
    ) {
        parent::__construct($context);
        $this->hid = $hid;
        $this->_apiKey = $this->getApiKey($storeManager->getStore()->getId());
        $this->_apiSecret = $this->getApiSecret($storeManager->getStore()->getId());
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->getClient();
    }

    public function getClient()
    {
        try {
            $this->client = new \Sailthru\MageSail\MageClient(
                $this->_apiKey,
                $this->_apiSecret,
                $this->logger,
                $this->storeManager
            );
        } catch (\Sailthru_Client_Exception $e) {
            $this->client = $e->getMessage();
            throw $e;
        }
        return true;
    }

    /* General */

    public function apiValidate()
    {
        try {
            $result = $this->client->getSettings();
            if (!array_key_exists("error", $result)) {
                return [1, self::API_SUCCESS_MESSAGE];
            }
        } catch (\Exception $e) {
            return [0, $e->getMessage()];
        }
    }

    public function isValid()
    {
        $check = $this->apiValidate();
        return $check[0];
    }

    public function getInvalidMessage()
    {
        return self::SOURCE_MODEL_VALIDATION_MSG;
    }

    public function getClientID()
    {
        return $this->getSettingsVal(self::XML_CLIENT_ID);
    }

    public function logger($message)
    {
        $this->client->logger($message);
    }

    public function getSettingsVal($val)
    {
        $storeCode = $this->_request->getParam('store');
        $websiteCode = $this->_request->getParam('website');
        if ($storeCode) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORE, $storeCode);
        } elseif ($websiteCode) {
            return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }
        return $this->scopeConfig->getValue($val, ScopeInterface::SCOPE_STORES);
    }

    /* Content */

    public function isProductInterceptOn()
    {
        return $this->getSettingsVal(self::XML_CONTENT_INTERCEPT);
    }

    public function canSyncMasterProducts()
    {
        return $this->getSettingsVal(self::XML_CONTENT_SEND_MASTER);
    }

    public function canSyncVariantProducts()
    {
        return $this->getSettingsVal(self::XML_CONTENT_SEND_VARIANTS);
    }

    public function tagsUseKeywords()
    {
        return $this->getSettingsVal(self::XML_CONTENT_USE_KEYWORDS);
    }

    public function tagsUseAttributes()
    {
        return $this->getSettingsVal(self::XML_CONTENT_USE_ATTRIBUTES);
    }

    public function tagsUseCategories()
    {
        return $this->getSettingsVal(self::XML_CONTENT_USE_CATEGORIES);
    }

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
                        $attribute_str .= (($value == "Yes" || $value == "Enabled") ? $key : $value) . ",";
                    }
                }
                $tags .= $attribute_str;
            }
        } catch (\Exception $e) {
            $this->logger($e);
        }
        return $tags;
    }

    public function getProductAttributeValues($product)
    {
        $setId = $product->getAttributeSetId();
        $attributeSet = $product->getAttributes();
        $data = [];
        foreach ($attributeSet as $attribute) {
            $label = $attribute->getName();
            if (!in_array($label, self::$unusedVarKeys)) {
                $value = $attribute->getFrontend()->getValue($product);
                if ($value && $label && $value != "No" && $value != " ") {
                    $data[$label] = $value;
                }
            }
        }
        return $data;
    }

    public function getCategories($product)
    {
        $collection = $product->getCategoryCollection();
        $items = $collection->addAttributeToSelect('name')->getItems();
        $categories = [];
        foreach ($items as $item) {
            $categories[] = $item->getName();
        }
        return $categories;
    }

    /* Abandoned Cart */

    public function isAbandonedCartEnabled()
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_ENABLED);
    }

    public function getAbandonedTemplate()
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_TEMPLATE);
    }

    public function getAbandonedTime()
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_TIME);
    }

    public function canAbandonAnonymous()
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_ANONYMOUS);
    }

    /* Transactionals */

    public function getTransactionalsEnabled()
    {
        return $this->getSettingsVal(self::XML_TRANSACTIONALS_ENABLED);
    }

    public function getSender()
    {
        return $this->getSettingsVal(self::XML_TRANSACTIONALS_SENDER);
    }

    public function getOrderOverride()
    {
        if ($this->getTransactionalsEnabled() &&
            $this->getSettingsVal(self::XML_ORDER_ENABLED) &&
            $this->getSettingsVal(self::XML_ORDER_TEMPLATE)) {
            return true;
        }
        return false;
    }

    public function getOrderTemplate()
    {
        return $this->getSettingsVal(self::XML_ORDER_TEMPLATE);
    }

    public function getBlastId()
    {
        return $this->hid->getBid();
    }

    public function getHid()
    {
        return $this->hid->get();
    }

    public function getAddressVars($address)
    {
        if (!$address) {
            return false;
        }
        $vars = [
            "countryCode"   => $address->getCountry(),
            "state"         => $address->getRegion(),
            "stateCode"     => $address->getRegionCode(),
            "city"          => $address->getCity(),
            "postal"        => $address->getPostcode(),
        ];
        return $vars;
    }

    public function getAddressVarsByCustomer($customer)
    {
        $address = $customer->getPrimaryBillingAddress();
        return $this->getAddressVars($address);
    }

    /**
     * To set Sailthru templates.
     */
    public function getSailthruTemplates()
    {
        if (empty($this->sailthruTemplates)) {
            $this->sailthruTemplates = $this->client->getTemplates();
        }

        return $this->sailthruTemplates;
    }

    /**
     * To create\update template in Sailthru.
     * 
     * @param  string $templateIdentifier
     * @param  string $sender
     */
    public function saveTemplate($templateIdentifier, $sender)
    {
        try {
            $templates = $this->getSailthruTemplates();
            $templates = isset($templates['templates'])
            ? array_column($templates['templates'], 'name')
            : [];

            if (!in_array($templateIdentifier, $templates)) {
                # Add template
                $data = [
                    "content_html" => "{content} {beacon}",
                    "subject" => "{subj}",
                    "from_email" => $sender,
                    "is_link_tracking" => 1
                ];
            } else {
                # Update template
                $data = [
                    "from_email" => $sender,
                ];
            }

            $response = $this->client->saveTemplate($templateIdentifier, $data);

            if (isset($response['error']))
                $this->client->logger($response['errormsg']);
        } catch (\Exception $e) {
            $this->client->logger($e->getMessage());
        }
    }

    private function getApiKey($storeId = null)
    {
        return $this->getSettingsVal(self::XML_API_KEY, $storeId);
    }

    private function getApiSecret($storeId = null)
    {
        return $this->getSettingsVal(self::XML_API_SECRET, $storeId);
    }
}
