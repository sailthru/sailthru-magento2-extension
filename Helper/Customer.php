<?php

namespace Sailthru\MageSail\Helper;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer as MageCustomer;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Logger;

class Customer extends AbstractHelper
{
    /** @var \Magento\Customer\Model\Group */
    protected $customerGroupCollection;

    /** @var \Magento\Framework\Stdlib\DateTime\Timezone */
    protected $timezone;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    ) {
        parent::__construct($context, $storeManager, $logger);

        $this->customerGroupCollection = $customerGroupCollection;
        $this->timezone = $timezone;
    }

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
     * @param  \Magento\Customer\Model\Customer $customerModel
     * 
     * @return array
     */
    public function getCustomerVars(MageCustomer $customerModel) {
        $data = $customerModel->getDataModel();
        $store = $this->storeManager->getStore($data->getStoreId());
        $group = $this->customerGroupCollection->load($data->getGroupId());
        $defaultBillingAddress = self::getAddress($customerModel->getDefaultBillingAddress());
        $defaultShippingAddress = self::getAddress($customerModel->getDefaultShippingAddress());

        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $data->getCreatedAt(),
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );

        return [
            'customer' => [
                'magento_id' => $data->getId(),
                'name' => $customerModel->getName(),
                'suffix' => $data->getSuffix() ? $data->getSuffix() : '',
                'prefix' => $data->getPrefix() ? $data->getPrefix() : '',
                'firstName' => $data->getFirstname() ? $data->getFirstname() : '',
                'middleName' => $data->getMiddlename() ? $data->getMiddlename() : '',
                'lastName' => $data->getLastname() ? $data->getLastname() : '',
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
    public function getAddress(Address $address)
    {
        if ($address) {
            $addressData = $address->getDataModel();
            $streets = $address->getStreet();

            return [
                'name' => $address->getName(),
                'company' => $addressData->getCompany(),
                'telephone' => $addressData->getTelephone(),
                'street1' => isset($streets[0]) ? $streets[0] : '',
                'street2' => isset($streets[1]) ? $streets[1] : '',
                'city' => $address->getCity(),
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
