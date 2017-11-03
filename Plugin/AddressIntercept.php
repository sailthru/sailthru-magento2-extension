<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Customer\Model\Address;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\Customer as SailthruCustomer;

class AddressIntercept
{

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCustomer $sailthruCustomer
    ) {
        $this->client = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCustomer = $sailthruCustomer;
    }

    public function afterSave(Address $subject, Address $addressResult)
    {
        $customerId = $addressResult->getCustomerId();
        $billing = $addressResult->getDataModel()->isDefaultBilling();
        if ($billing) {
            $customer = $addressResult->getCustomer();
            $sid      = $customer->getData('sailthru_id');
            $email    = $customer->getEmail();
            $addressVars  = $this->sailthruCustomer->getAddressVars($addressResult);
            $data = [
                'id' => $sid ? $sid : $email,
                'vars' => $addressVars,
            ];
            try {
                $this->client = $this->client->getClient(true, $customer->getStore()->getId());
                $this->client->_eventType = 'CustomerAddressUpdate';
                $this->client->apiPost('user', $data);
            } catch (\Sailthru_Client_Exception $e) {
                $this->client->logger($e->getMessage());
            } catch (\Exception $e) {
                $this->client->logger($e->getMessage());
            } finally {
                return $addressResult;
            }
        } else {
            return $addressResult;
        }
    }
}
