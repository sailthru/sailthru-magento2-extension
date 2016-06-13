<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Api extends AbstractHelper
{

	protected $_scopeConfig;
	public $client;

	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	){
		$this->_scopeConfig = $scopeConfig;
		$api_key = $this->_scopeConfig->getValue('sailthru_api/magesail_api/sailthru_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		error_log("hello world! " . $api_key);
		$api_secret = $this->_scopeConfig->getValue('sailthru_api/magesail_api/sailthru_private', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		error_log("hello world! " . $api_secret);
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

	public function api_validate(){
		$result = $this->client->getSettings();
		if (sizeof($result) and array_key_exists("lists_primary", $result) and array_key_exists("from_emails", $result)) {
			return [1, $result];
		} else 
		{
			return [0, $result];
		}
	}



}