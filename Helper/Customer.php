<?php

namespace Sailthru\MageSail\Helper;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer as MageCustomer;

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

    /**
     * To get Customer Info(Array).
     * 
     * @param  \Magento\Customer\Model\Customer             $customerModel
     * @param  \Magento\Store\Model\StoreManagerInterface   $storeManager
     * @param  \Magento\Customer\Model\Group                $groupCollection
     * @param  \Magento\Framework\Stdlib\DateTime\Timezone  $timezone
     * 
     * @return array
     */
    public function getCustomerVariable(
        $customerModel,
        $storeManager,
        $groupCollection,
        $timezone
    ) {
        $data = $customerModel->getData();
        $store = $storeManager->getStore($data['store_id']);
        $group = $groupCollection->load($data['group_id']);
        $defaultBillingAddress = self::getAddress($customerModel->getDefaultBillingAddress());
        $defaultShippingAddress = self::getAddress($customerModel->getDefaultShippingAddress());

        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $data['created_at'],
            new \DateTimeZone($timezone->getConfigTimezone())
        );

        return [
            'customer' => [
                'magento_id' => $data['entity_id'],
                'name' => $customerModel->getName(),
                'suffix' => $data['suffix'],
                'prefix' => $data['prefix'],
                'firstName' => $data['firstname'],
                'middleName' => $data['middlename'],
                'lastName' => $data['lastname'],
                'store' => $store->getName(),
                'customerGroup' => $group->getCustomerGroupCode(),
                'created_date' => $date->format('Y-m-d'),
                'created_time' => $date->getTimestamp(),
                'defaultBillingAddress' => $defaultBillingAddress,
                'defaultShippingAddress' => $defaultShippingAddress,
            ],
            'registration' => [
                'name' => $customerModel->getName(),
            ],
        ];
    }

    /**
     * To get formatted address information.
     * 
     * @param  Magento\Customer\Model\Address $address
     * 
     * @return array
     */
    public function getAddress($address)
    {
        if ($address) {
            $addressData = $address->getData();
            $streets = $address->getStreet();

            return [
                'name' => $address->getName(),
                'company' => $addressData['company'],
                'telephone' => $addressData['telephone'],
                'street1' => $streets[0],
                'street2' => $streets[1],
                'city' => $addressData['city'],
                'state' => $address->getRegion(),
                'state_code' => $address->getRegionCode(),
                'country' => $address->getCountryModel()->getName(),
                'country_code' => $address->getCountry(),
                'postal_code' => $address->getPostcode(),
            ];
        }

        return [];
    }
}
