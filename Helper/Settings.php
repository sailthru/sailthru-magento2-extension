<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
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
     * To check If a template is specified.
     * 
     * @param  string $id
     * @return string|null
     */
    public function getTemplateEnabled($id)
    {
        return $this->getSettingsVal('magesail_send/transactionals/'.$id.'_enabled');
    }

    /**
     * To get template value.
     * 
     * @param  string $id
     * @return string|null
     */
    public function getTemplateValue($id)
    {
        return $this->getSettingsVal('magesail_send/transactionals/'.$id);
    }
}
