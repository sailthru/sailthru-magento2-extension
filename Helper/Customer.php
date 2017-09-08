<?php

namespace Sailthru\MageSail\Helper;

use Sailthru\MageSail\Helper\VariablesAbstractHelper;
use Magento\Customer\Model\Address;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\Customer as MageCustomer;

class Customer extends VariablesAbstractHelper
{
    const CUSTOMER_MODEL = 'Magento\Customer\Model\Customer';
    const CUSTOMER_GROUP_MODEL = 'Magento\Customer\Model\Group';
    const DATETIME_TIMEZONE = 'Magento\Framework\Stdlib\DateTime\Timezone';

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
        $group = ObjectManager::getInstance()->create(self::CUSTOMER_GROUP_MODEL)->load($dataModel->getGroupId());

        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $dataModel->getCreatedAt(),
            new \DateTimeZone(
                ObjectManager::getInstance()->create(self::DATETIME_TIMEZONE)->getConfigTimezone()
            )
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
        $customer = ObjectManager::getInstance()->create(self::CUSTOMER_MODEL);
        $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer->loadByEmail($param);

        return $customer;
    }
}
