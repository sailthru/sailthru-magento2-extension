<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Api extends AbstractHelper
{

	protected $_scopeConfig;
	public $client;
	public $hid;

	const API_SUCCESS_MESSAGE = "Success! Sail away!";

	const VALIDATION_NEEDED_MSG = "Please Enter Valid Sailthru Credentials";

	public function __construct(
		\Magento\Framework\App\MutableScopeConfig $scopeConfig,
		\Sailthru\MageSail\Cookie\Hid $hid
	){
		$this->_scopeConfig = $scopeConfig;
		$this->hid = $hid;
		$api_key = $this->_scopeConfig->getValue('magesail_config/service/api_key');
		$api_secret = $this->_scopeConfig->getValue('magesail_config/service/secret_key');
		// $valid_keys = $this->_scopeConfig->getValue('magesail_config/service/magesail_api/valid_keys');
		// error_log("valid keys = " . $valid_keys);
		// error_log("api key is: $api_key");
		// error_log("secret key is: $api_key");
		$this->getClient($api_key, $api_secret);
	}

	public function getClient($api_key, $api_secret){
		try {
			$this->client = new \Sailthru\MageSail\MageClient($api_key, $api_secret, '/var/log/sailthru.log');
		}
		catch (\Sailthru_Client_Exception $e) {
			$this->client = $e->getMessage();
			error_log($e->getMessage());
		}
		return true;
	}

	public function apiValidate(){
		$result = $this->client->getSettings();
		if (!array_key_exists("error", $result)) {
			return [1, "Set Sail!"];
		} else {
			return [0, $result["errormsg"]];
		}
	}

	public function isValid(){
		$check = $this->apiValidate();
		return $check[0];
	}

	public function getInvalidMessage(){
		return self::VALIDATION_NEEDED_MSG;
	}

	public function getClientID(){
		return $this->_scopeConfig->getValue('magesail_personalize/settings/client/customer_id');
	}

	public function logger($message){
		$this->client->logger($message);
	}

	public function getSettingsVal($val){
		return $this->_scopeConfig->getValue($val);
	}

}