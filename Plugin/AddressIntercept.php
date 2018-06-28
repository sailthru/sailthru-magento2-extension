<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Customer\Model\Address;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\ScopeResolver;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\Customer as SailthruCustomer;

class AddressIntercept
{
    private $sailthruSettings;
    private $sailthruCustomer;
    private $scopeResolver;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCustomer $sailthruCustomer,
        ScopeResolver $scopeResolver
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCustomer = $sailthruCustomer;
        $this->scopeResolver = $scopeResolver;
    }

    public function afterSave(Address $subject, Address $addressResult)
    {
        $billing = $addressResult->getDataModel()->isDefaultBilling();
        if (!$billing) {
            return $addressResult;
        }

        $storeId = $addressResult->getCustomer()->getStore()->getId();
        $this->scopeResolver->emulateStore($storeId);
        $customer = $addressResult->getCustomer();
        $sid = $customer->getData('sailthru_id');
        $email = $customer->getEmail();
        $addressVars = $this->sailthruCustomer->getAddressVars($addressResult);
        $data = [
            'id' => $sid ? $sid : $email,
            'vars' => $addressVars,
        ];
        try {
            $client = $this->clientManager->getClient(true, $customer->getStore()->getId());
            $client->_eventType = 'CustomerAddressUpdate';
            $client->apiPost('user', $data);
        } catch (\Exception $e) {
            $this->clientManager->logger($e->getMessage());
        } finally {
            $this->scopeResolver->stopEmulation();
            return $addressResult;
        }
    }
}
