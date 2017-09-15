<?php

namespace Sailthru\MageSail\Helper;

use Sailthru\MageSail\Helper\VariablesAbstractHelper;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer as MageCustomer;
use Magento\Customer\Model\Group;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

class Customer extends VariablesAbstractHelper
{
    /** @var MageCustomer */
    private $customerModel;

    /** @var Magento\Customer\Model\Group */
    private $customerGroupModel;

    /** @var Magento\Framework\Stdlib\DateTime\Timezone */
    private $dateTime;

    public function __construct(
        MageCustomer $customerModel,
        Group $customerGroupModel,
        Timezone $dateTime,
        Context $context,
        StoreManager $storeManager,
        CountryInformationAcquirerInterface $countryInformation
    ) {
        parent::__construct($context, $storeManager, $countryInformation);

        $this->customerModel = $customerModel;
        $this->customerGroupModel = $customerGroupModel;
        $this->dateTime = $dateTime;
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
     * To get Customer additional variables.
     * 
     * @param  MageCustomer $object
     * @param  array        $data
     * 
     * @return array
     */
    public function getCustomVariables($object, $data = [])
    {        
        $dataModel = $object->getDataModel();
        $store = $this->storeManager->getStore($dataModel->getStoreId());
        $group = $this->customerGroupModel->load($dataModel->getGroupId());

        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $dataModel->getCreatedAt(),
            new \DateTimeZone($this->dateTime->getConfigTimezone())
        );

        return [
            'customer' => [
                'magento_id' => $dataModel->getId(),
                'name' => $object->getName(),
                'suffix' => $dataModel->getSuffix() ?? '',
                'prefix' => $dataModel->getPrefix() ?? '',
                'firstName' => $dataModel->getFirstname() ?? '',
                'middleName' => $dataModel->getMiddlename() ?? '',
                'lastName' => $dataModel->getLastname() ?? '',
                'store' => $store->getName(),
                'customerGroup' => $group->getCustomerGroupCode(),
                'created_date' => $date->format('Y-m-d'),
                'created_time' => $date->getTimestamp(),
                'defaultBillingAddress' => $this->getAddress($object->getDefaultBillingAddress()),
                'defaultShippingAddress' => $this->getAddress($object->getDefaultShippingAddress()),
            ],
            'registration' => [
                'name' => $object->getName(),
            ],
        ];
    }

    /**
     * To get Customer model.
     * 
     * @param  string $param
     * 
     * @return \Magento\Customer\Model\Customer
     */
    public function getObject($param)
    {
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $this->customerModel->loadByEmail($param);

        return $this->customerModel;
    }
}
