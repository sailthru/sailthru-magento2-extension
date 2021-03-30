<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\ObjectManager;
use Sailthru\MageSail\Cookie\Hid;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;

class Settings extends AbstractHelper
{
    protected $_apiKey;
    protected $_apiSecret;
    public $client;
    public $hid;
    protected $productMetadataInterface;

    // Source models
    const SOURCE_MODEL_VALIDATION_MSG  = "Please Enter Valid Sailthru Credentials";

    // TODO
    // const XML_CUSTOMER_ID              = "magesail_"

    // Lists
    const XML_ONREGISTER_LIST_ENABLED       = "magesail_lists/lists/enable_signup_list";
    const XML_ONREGISTER_LIST_VALUE         = "magesail_lists/lists/signup_list";
    const XML_NEWSLETTER_LIST_ENABLED       = "magesail_lists/lists/enable_newsletter";
    const XML_NEWSLETTER_LIST_VALUE         = "magesail_lists/lists/newsletter_list";
    const XML_SELECT_CASE                   = "magesail_lists/names/select_case";

    // Transactional Emails
    const XML_ABANDONED_CART_ENABLED   = "magesail_send/abandoned_cart/enabled";
    const XML_ABANDONED_CART_TEMPLATE  = "magesail_send/abandoned_cart/template";
    const XML_ABANDONED_CART_TIME      = "magesail_send/abandoned_cart/delay_time";
    const XML_ABANDONED_CART_ANONYMOUS = "magesail_send/abandoned_cart/anonymous_carts";
    const XML_TRANSACTIONALS_ENABLED   = "magesail_send/transactionals/send_through_sailthru";
    const XML_TRANSACTIONALS_SENDER    = "magesail_send/transactionals/from_sender";
    const XML_ORDER_ENABLED            = "magesail_send/transactionals/purchase_enabled";
    const XML_ORDER_TEMPLATE           = "magesail_send/transactionals/purchase_template";
    const XML_TEMPLATES_CACHE_LIFETIME = "magesail_send/advanced/templates_cache_lifetime";
    const XML_USE_EMAIL_QUEUE          = "magesail_send/advanced/use_email_queue";
    const LO_ABANDONED_CART_ENABLED    = "1";

    // Queue settings
    const QUEUE_ATTEMPTS_COUNT = 3;

    /** Path to the `transactionals` tab. */
    const XML_TRANSACTIONALS_PATH = 'magesail_send/transactionals/';

    /** Legacy variables template map. */
    const HELPERS_MAP = [
        'Sailthru\MageSail\Helper\Customer' => [
            'customer_create_account_email_template',
            'customer_create_account_email_confirmation_template',
            'customer_create_account_email_confirmed_template',
        ],
        'Sailthru\MageSail\Helper\Order' => [
            'sales_email_order_guest_template',
            'sales_email_order_template',
        ],
        'Sailthru\MageSail\Helper\Shipment' => [
            'sales_email_shipment_template',
            'sales_email_shipment_guest_template',
        ],
    ];

    /** Templates list where Magento 1 legacy variables will be injected. */
    const TEMPLATES_WITH_IS_GUEST_VAR = [
        'sales_email_order_template',
        'sales_email_order_comment_template',
        'sales_email_order_comment_guest_template',
        'sales_email_shipment_template',
        'sales_email_shipment_guest_template',
        'sales_email_shipment_comment_template',
        'sales_email_shipment_comment_guest_template',
    ];

    /** Magento `Generic` template name. */
    const MAGENTO_GENERIC_TEMPLATE = 'Magento Generic';

    /** Prefix for template name. */
    const MAGENTO_PREFIX = 'magento_';

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ScopeResolver $scopeResolver,
        ProductMetadataInterface $productMetadataInterface
    ) {
        parent::__construct($context, $storeManager, $logger, $templateModel, $templateConfig, $objectManager, $scopeResolver);
        $this->productMetadataInterface = $productMetadataInterface;
    }

    public function getInvalidMessage()
    {
        return self::SOURCE_MODEL_VALIDATION_MSG;
    }

//    TODO: implement CID as field
//    public function getClientID()
//    {
//        return $this->getSettingsVal(self::XML_CLIENT_ID);
//    }

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

    /* Abandoned Cart */

    public function isAbandonedCartEnabled($storeId = null)
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_ENABLED, $storeId);
    }

    public function getAbandonedTemplate($storeId = null)
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_TEMPLATE, $storeId);
    }

    public function getAbandonedTime($storeId = null)
    {
        return $this->getSettingsVal(self::XML_ABANDONED_CART_TIME, $storeId);
    }

    public function canAbandonAnonymous($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_ABANDONED_CART_ANONYMOUS, $storeId));
    }

    /* Transactionals */

    public function getTransactionalsEnabled($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_TRANSACTIONALS_ENABLED, $storeId));
    }

    public function getTemplatesCacheLifetime($storeId = null)
    {
        return $this->getSettingsVal(self::XML_TEMPLATES_CACHE_LIFETIME, $storeId);
    }

    public function getUseEmailQueue($storeId = null)
    {
        return $this->getSettingsVal(self::XML_USE_EMAIL_QUEUE, $storeId);
    }

    public function getSender($storeId = null)
    {
        return $this->getSettingsVal(self::XML_TRANSACTIONALS_SENDER, $storeId);
    }

    public function getOrderOverride($storeId = null)
    {
        if ($this->getTransactionalsEnabled($storeId) &&
            $this->getSettingsVal(self::XML_ORDER_ENABLED, $storeId) &&
            $this->getSettingsVal(self::XML_ORDER_TEMPLATE, $storeId)) {
            return true;
        }
        return false;
    }

    public function getOrderTemplate($storeId = null)
    {
        return $this->getSettingsVal(self::XML_ORDER_TEMPLATE, $storeId);
    }

    public function customerListEnabled($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_ONREGISTER_LIST_ENABLED, $storeId));
    }

    public function getCustomerList($storeId = null)
    {
        return $this->getSettingsVal(self::XML_ONREGISTER_LIST_VALUE, $storeId);
    }

    public function newsletterListEnabled($storeId = null)
    {
        return boolval($this->getSettingsVal(self::XML_NEWSLETTER_LIST_ENABLED, $storeId));
    }

    public function getNewsletterList($storeId = null)
    {
        return $this->getSettingsVal(self::XML_NEWSLETTER_LIST_VALUE, $storeId);
    }

    public function getSelectCase($storeId = null)
    {
        return $this->getSettingsVal(self::XML_SELECT_CASE, $storeId);
    }
    /**
     * To get template name.
     * 
     * @param  string      $templateId
     * @param  string|null $storeId
     * 
     * @return array
     */
    public function getTemplateName($templateId, $storeId = null)
    {
        $templateData = $this->templateModel->getTemplateDataById($templateId);

        if ($templateData) {
            if ($templateData['orig_template_code']) {
                $value = $this->getTemplateValue($templateData['orig_template_code'], $storeId);
                $name = empty($value)
                    ? self::MAGENTO_PREFIX . $templateData['template_code']
                    : $value;
                $origCode = $templateData['orig_template_code'];
            } else {
                $templates = $this->templateConfig->get('templates');
                $ids = [];
                foreach ($templates as $template) {
                    if ($templateData['template_id'] == $this->getSettingsVal(
                        $template['custom_template_source'],
                        $storeId
                    )) {
                        $ids[] = $template['id'];
                    }
                }

                if (count($ids) > 1) {
                    return [
                        'name' => self::MAGENTO_GENERIC_TEMPLATE,
                        'orig_template_code' => self::MAGENTO_GENERIC_TEMPLATE,
                    ];
                }

                $value = empty($ids) ? null : $this->getTemplateValue($ids[0], $storeId);
                $name = empty($value)
                    ? self::MAGENTO_PREFIX . $templateData['template_code']
                    : $value;
                $origCode = empty($ids) ? '' : $ids[0];
            }
        } else {
            $value = $this->getTemplateValue($templateId, $storeId);
            $name = empty($value) ? self::MAGENTO_PREFIX . $templateId : $value;
            $origCode = $templateId;
        }

        return [
            'name' => $name,
            'orig_template_code' => $origCode,
        ];
    }

    /**
     * To get template value.
     * 
     * @param  string      $id
     * @param  string|null $storeId
     * 
     * @return string|null
     */
    public function getTemplateValue($id, $storeId = null)
    {
        return $this->getSettingsVal(self::XML_TRANSACTIONALS_PATH . $id, $storeId);
    }

    /**
     * To get additional variables for given template.
     * 
     * @param  string  $id
     * @param  array   $currentVars
     * 
     * @return array
     */
    public function getTemplateAdditionalVariables($id, $currentVars = [])
    {
        $helper = $this->getHelperByTemplateId($id);

        # Magento 1 legacy variables mapping.
        if (in_array($id, self::HELPERS_MAP['Sailthru\MageSail\Helper\Customer'])) {
            # Get customer id.
            if (isset($currentVars['reset_url']) && preg_match('/id=(.*?)\&/', $currentVars['reset_url'], $matches)) {
                $customer = $helper->getObjectById($matches[1] ?? null);
            } else {
                $customer = isset($currentVars['customer_email']) ?
                    $helper->getObject($currentVars['customer_email'])
                    : null;
            }

            if ($customer) {
                $currentVars += $helper->getCustomVariables($customer);
            }
        }

        if (in_array($id, self::HELPERS_MAP['Sailthru\MageSail\Helper\Order'])) {
            if (isset($currentVars['increment_id'])) {
                $order = $helper->getObject($currentVars['increment_id']);
                $currentVars += $helper->getCustomVariables($order);
                
                if (in_array($id, self::TEMPLATES_WITH_IS_GUEST_VAR)) {
                    $currentVars += $helper->getIsGuestVariable($order);
                }
            }
        }

        if (in_array($id, self::HELPERS_MAP['Sailthru\MageSail\Helper\Shipment'])) {
            if (isset($currentVars['shipment_id'])) {
                $shipment = $helper->getObject($currentVars['shipment_id']);
                $currentVars += $helper->getCustomVariables($shipment);

                if (in_array($id, self::TEMPLATES_WITH_IS_GUEST_VAR)) {
                    $currentVars += $helper->getIsGuestVariable($shipment);
                }
            }
        }

        return $currentVars;
    }

    /**
     * To get helper by template identifier.
     * 
     * @param  string $templateId
     * 
     * @return mixed
     */
    public function getHelperByTemplateId($templateId)
    {
        foreach (self::HELPERS_MAP as $helper => $templates) {
            if (in_array($templateId, $templates)) {
                return $this->objectManager->create($helper);
            }
        }

        return null;
    }

    /**
     * To get magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadataInterface->getVersion();
    }
}
