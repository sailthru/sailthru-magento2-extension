<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Api extends AbstractHelper
{

	protected $_scopeConfig;
	public $client;

	const API_SUCCESS_MESSAGE = "Success! Sail away!";

	public function __construct(
		\Magento\Framework\App\MutableScopeConfig $scopeConfig
	){
		$this->_scopeConfig = $scopeConfig;
		$api_key = $this->_scopeConfig->getValue('magesail_config/service/api_key');
		$api_secret = $this->_scopeConfig->getValue('magesail_config/service/secret_key');
		$valid_keys = $this->_scopeConfig->getValue('magesail_config/service/magesail_api/valid_keys');
		error_log("valid keys = " . $valid_keys);
		// error_log("api key is: $api_key");
		// error_log("secret key is: $api_key");
		$this->getClient($api_key, $api_secret);
	}

	public function getClient($api_key, $api_secret){
		require(__DIR__ . '/../Client/Api/Sailthru_Client.php');
		require(__DIR__ . '/../Client/Api/Sailthru_Client_Exception.php');
		require(__DIR__ . '/../Client/Api/Sailthru_Util.php');
		try {
			$this->client = new \Sailthru\MageSail\Client\Api\Sailthru_Client($api_key, $api_secret);
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
			$this->_scopeConfig->setValue('sailthru_api/magesail_api/valid_keys', 1);
			return [1, "Set Sail!"];
		} else 
		{
			$this->_scopeConfig->setValue('sailthru_api/magesail_api/valid_keys', 0);
			return [0, $result["errormsg"]];
		}
	}

	public function isValid(){
		$check = $this->apiValidate();
		return $check[0];
	}



}