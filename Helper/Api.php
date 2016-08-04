<?php

namespace Sailthru\MageSail\Helper;

use Sailthru\MageSail\Cookie\Hid;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\MutableScopeConfig;


class Api extends AbstractHelper
{

	protected $_scopeConfig;
	protected $_apiKey;
	protected $_apiSecret;
	public $client;
	public $hid;

	// Source models
	const SOURCE_MODEL_VALIDATION_MSG  = "Please Enter Valid Sailthru Credentials";

	// Settings main
	const XML_API_KEY				   = "magesail_config/service/api_key";
	const XML_API_SECRET			   = "magesail_config/service/secret_key";
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
    const XML_TRANSACTIONALS_SENDER	   = "magesail_send/transactionals/from_sender";
    const XML_ORDER_ENABLED			   = "magesail_send/transactionals/purchase_enabled";
    const XML_ORDER_TEMPLATE		   = "magesail_send/transactionals/purchase_template";

    // Content
    const XML_CONTENT_INTERCEPT 	   = "magesail_content/content/enable_intercept";
    const XML_CONTENT_USE_KEYWORDS	   = "magesail_content/content/tags_seo";
    const XML_CONTENT_USE_CATEGORIES   = "magesail_content/content/tags_categories";
    const XML_CONTENT_USE_ATTRIBUTES   = "magesail_content/content/tags_attributes";

	
	public function __construct(MutableScopeConfig $scopeConfig, Hid $hid)
	{
		$this->_scopeConfig = $scopeConfig;
		$this->hid = $hid;
		$this->_apiKey = $this->getApiKey();
		$this->_apiSecret = $this->getApiSecret();
		$this->getClient();
	}

	protected function getApiKey(){
		return $this->_scopeConfig->getValue(self::XML_API_KEY);
	}

	protected function getApiSecret(){
		return $this->_scopeConfig->getValue(self::XML_API_SECRET);
	}

	protected function getClient()
	{
		try {
			$this->client = new \Sailthru\MageSail\MageClient($this->_apiKey, $this->_apiSecret, '/var/log/sailthru.log');
		}
		catch (\Sailthru_Client_Exception $e) {
			$this->client = $e->getMessage();
			error_log($e->getMessage());
		}
		return true;
	}

	/* General */

	public function apiValidate()
	{
		$result = $this->client->getSettings();
		if (!array_key_exists("error", $result)) {
			return [1, self::API_SUCCESS_MESSAGE];
		} else {
			return [0, $result["errormsg"]];
		}
	}

	public function isValid(){
		$check = $this->apiValidate();
		return $check[0];
	}

	public function getInvalidMessage(){
		return self::SOURCE_MODEL_VALIDATION_MSG;
	}

	public function getClientID(){
		return $this->_scopeConfig->getValue(self::XML_CLIENT_ID);
	}

	public function logger($message){
		$this->client->logger($message);
	}

	public function getSettingsVal($val){
		return $this->_scopeConfig->getValue($val);
	}

	/* Content */

	public function isProductInterceptOn(){
		return $this->getSettingsVal(self::XML_CONTENT_INTERCEPT);
	}

	public function tagsUseKeywords(){
		return $this->getSettingsVal(self::XML_CONTENT_USE_KEYWORDS);
	}

	public function tagsUseAttributes(){
		return $this->getSettingsVal(self::XML_CONTENT_USE_ATTRIBUTES);
	}

	public function tagsUseCategories(){
		return $this->getSettingsVal(self::XML_CONTENT_USE_CATEGORIES);
	}


	/* Abandoned Cart */


	public function isAbandonedCartEnabled(){
		return $this->getSettingsVal(self::XML_ABANDONED_CART_ENABLED);
	}

	public function getAbandonedTemplate(){
		return $this->getSettingsVal(self::XML_ABANDONED_CART_TEMPLATE);
	}

	public function getAbandonedTime(){
		return $this->getSettingsVal(self::XML_ABANDONED_CART_TIME);
	}

	public function canAbandonAnonymous(){
		return $this->getSettingsVal(self::XML_ABANDONED_CART_ANONYMOUS);
	}

	/* Transactionals */

	public function getTransactionalsEnabled(){
		return $this->getSettingsVal(self::XML_TRANSACTIONALS_ENABLED);
	}

	public function getSender(){
		return $this->getSettingsVal(self::XML_TRANSACTIONALS_SENDER);
	}

	public function getOrderOverride(){
		if ($this->getTransactionalsEnabled() && 
			$this->getSettingsVal(self::XML_ORDER_ENABLED) &&
			$template = $this->getSettingsVal(self::XML_ORDER_TEMPLATE)){
			return $template;
		}
		return false;
	}

	public function getBlastId(){
		return $this->hid->getBid();
	}

	public function getHid(){
		return $this->hid->get();
	}



}