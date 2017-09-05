<?php

namespace Sailthru\MageSail\Helper;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer as MageCustomer;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Logger;

class Customer extends AbstractHelper
{
    public function getAddressVars(Address $address)
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

    public function getAddressVarsByCustomer(MageCustomer $customer)
    {
        $address = $customer->getPrimaryBillingAddress();
        return $this->getAddressVars($address);
    }
}
