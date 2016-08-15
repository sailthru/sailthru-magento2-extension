<?php

namespace Sailthru\MageSail\Plugin;

use Sailthru\MageSail\Helper\Api;

class AddressIntercept
{

	public function __construct(Api $sailthru){
		$this->sailthru = $sailthru;
	}

	public function afterSave($subject, $addressResult){
		try {	
			$customer = $addressResult->getCustomer();
			$sid	  = $customer->getData('sailthru_id');
			$email	  = $customer->getEmail();
			$addressVars  = $this->sailthru->getAddressVars($addressResult);
			$data = [
				'id' => $sid ? $sid : $email,
				'vars' => $addressVars,
			];
            $this->sailthru->client->_eventType = 'CustomerAddressUpdate';
		    $response = $this->sailthru->client->apiPost('user', $data);
		    $this->sailthru->client->_eventType = '';
		} catch (\Exception $e){
			$this->sailthru->logger($e->getMessage());
		} finally {
			return $addressResult;
		}
	}

}