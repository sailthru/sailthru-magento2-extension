<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Sailthru\MageSail\Cookie\Hid;

class Settings extends AbstractHelper
{

    protected $_apiKey;
    protected $_apiSecret;
    public $client;
    public $hid;

    // Source models
    const SOURCE_MODEL_VALIDATION_MSG  = "Please Enter Valid Sailthru Credentials";

    // TODO
    // const XML_CUSTOMER_ID              = "magesail_"

    // Lists
    const XML_ONREGISTER_LIST_ENABLED  = "magesail_lists/lists/enable_signup_list";
    const XML_ONREGISTER_LIST_VALUE    = "magesail_lists/lists/signup_list";
    const XML_NEWSLETTER_LIST_ENABLED  = "magesail_lists/lists/enable_newsletter";
    const XML_NEWSLETTER_LIST_VALUE    = "magesail_lists/lists/newsletter_list";

    // Transactional Emails
    const XML_ABANDONED_CART_ENABLED   = "magesail_send/abandoned_cart/enabled";
    const XML_ABANDONED_CART_TEMPLATE  = "magesail_send/abandoned_cart/template";
    const XML_ABANDONED_CART_TIME      = "magesail_send/abandoned_cart/delay_time";
    const XML_ABANDONED_CART_ANONYMOUS = "magesail_send/abandoned_cart/anonymous_carts";
    const XML_TRANSACTIONALS_ENABLED   = "magesail_send/transactionals/send_through_sailthru";
    const XML_TRANSACTIONALS_SENDER    = "magesail_send/transactionals/from_sender";
    const XML_ORDER_ENABLED            = "magesail_send/transactionals/purchase_enabled";
    const XML_ORDER_TEMPLATE           = "magesail_send/transactionals/purchase_template";

    /** Helper map for templates */
    const HELPERS_MAP = [
        'Sailthru\MageSail\Helper\Customer' => [
            'customer_create_account_email_template',
            'customer_create_account_email_confirmation_template',
            'customer_create_account_email_confirmed_template',
        ],
        'Sailthru\MageSail\Helper\Order' => [
            'sales_email_order_template',
        ],
        'Sailthru\MageSail\Helper\Shipment' => [
            'sales_email_shipment_template',
        ],
    ];

    /** Templates list where Magento 1 legacy variables will be injected. */
    const CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'customer_create_account_email_template',
        'customer_create_account_email_confirmation_template',
        'customer_create_account_email_confirmed_template',
    ];

    /** Templates list where Magento 1 legacy variables will be injected. */
    const ORDER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'sales_email_order_template',
    ];

    /** Templates list where Magento 1 legacy variables will be injected. */
    const SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'sales_email_shipment_template',
    ];

    /** Templates list where Magento 1 legacy variables will be injected. */
    const TEMPLATES_WITH_IS_GUEST_VAR = [
        'sales_email_order_template',
        'sales_email_order_comment_template',
        'sales_email_order_comment_guest_template',
        'sales_email_shipment_template',
        'sales_email_shipment_comment_template',
        'sales_email_shipment_comment_guest_template',
    ];

    /** Magento `Generic` template name. */
    const MAGENTO_GENERIC_TEMPLATE = 'Magento Generic';

    /** Prefix for template name. */
    const MAGENTO_PREFIX = 'magento_';


    public function getInvalidMessage()
    {
        return self::SOURCE_MODEL_VALIDATION_MSG;
    }

//    TODO: implement CID as field
//    public function getClientID()
//    {
//        return $this->getSettingsVal(self::XML_CLIENT_ID);
//    }

    /* Abandoned Cart */

    public function isAbandonedCartEnabled()
    {
        return boolval($this->getSettingsVal(self::XML_ABANDONED_CART_ENABLED));
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
        return boolval($this->getSettingsVal(self::XML_ABANDONED_CART_ANONYMOUS));
    }

    /* Transactionals */

    public function getTransactionalsEnabled()
    {
        return boolval($this->getSettingsVal(self::XML_TRANSACTIONALS_ENABLED));
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

    public function customerListEnabled()
    {
        return boolval($this->getSettingsVal(self::XML_ONREGISTER_LIST_ENABLED));
    }

    public function getCustomerList()
    {
        return $this->getSettingsVal(self::XML_ONREGISTER_LIST_VALUE);
    }

    public function newsletterListEnabled()
    {
        return boolval($this->getSettingsVal(self::XML_NEWSLETTER_LIST_ENABLED));
    }

    public function getNewsletterList()
    {
        return $this->getSettingsVal(self::XML_NEWSLETTER_LIST_VALUE);
    }

    /**
     * To get template name.
     * 
     * @param  string $templateId
     * 
     * @return array
     */
    public function getTemplateName($templateId)
    {
        $templateData = $this->templateModel->getTemplateDataById($templateId);

        if ($templateData) {
            if ($templateData['orig_template_code']) {
                $value = $this->getTemplateValue($templateData['orig_template_code']);
                $name = $value == '0' ? self::MAGENTO_PREFIX . $templateData['template_code'] : $value;
                $origCode = $templateData['orig_template_code'];
            } else {
                $templates = $this->templateConfig->get('templates');
                $ids = [];
                foreach ($templates as $template) {
                    if ($templateData['template_id'] == $this->getSettingsVal($template['custom_template_source'])) {
                        $ids[] = $template['id'];
                    }
                }

                if (count($ids) > 1) {
                    return [
                        'name' => self::MAGENTO_GENERIC_TEMPLATE,
                        'orig_template_code' => self::MAGENTO_GENERIC_TEMPLATE,
                    ];
                }

                $value = $this->getTemplateValue($ids[0]);
                $name = $value == '0' ? self::MAGENTO_PREFIX . $templateData['template_code'] : $value;
                $origCode = $ids[0];
            }
        } else {
            $value = $this->getTemplateValue($templateId);
            $name = $value == '0' ? self::MAGENTO_PREFIX . $templateId : $value;
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
     * @param  string $id
     * 
     * @return string|null
     */
    public function getTemplateValue($id)
    {
        return $this->getSettingsVal('magesail_send/transactionals/' . $id);
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
        if (in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            # Get customer id.
            if (isset($currentVars['reset_url']) && preg_match('/id=(.*?)\&/', $currentVars['reset_url'], $matches)) {
                $customer = $helper->getObjectById($matches[1] ?? null);
            } else {
                $customer = $helper->getObject($currentVars['customer_email']);
            }
            $currentVars += $helper->getCustomVariables($customer);
        }

        if (in_array($id, self::ORDER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            $order = $helper->getObject($currentVars['increment_id']);
            $currentVars += $helper->getCustomVariables($order);

            if (in_array($id, self::TEMPLATES_WITH_IS_GUEST_VAR)) {
                $currentVars += $helper->getIsGuestVariable($order);
            }
        }

        if (in_array($id, self::SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            $shipment = $helper->getObject($currentVars['shipment_id']);
            $currentVars += $helper->getCustomVariables($shipment);

            if (in_array($id, self::TEMPLATES_WITH_IS_GUEST_VAR)) {
                $currentVars += $helper->getIsGuestVariable($shipment);
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
                return ObjectManager::getInstance()->create($helper);
            }
        }

        return null;
    }
}
