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
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\VarHelper;

class Customer extends VariablesAbstractHelper
{
    /** @var MageCustomer */
    private $customerModel;

    /** @var Magento\Customer\Model\Group */
    private $customerGroupModel;

    /** @var Magento\Framework\Stdlib\DateTime\Timezone */
    private $dateTime;

    private $sailthruSettings;

    private $sailthruVars;

    public function __construct(
        MageCustomer $customerModel,
        Group $customerGroupModel,
        Timezone $dateTime,
        Context $context,
        StoreManager $storeManager,
        CountryInformationAcquirerInterface $countryInformation,
        SailthruSettings $sailthruSettings,
        VarHelper $sailthruVars
    ) {
        parent::__construct($context, $storeManager, $countryInformation);

        $this->customerModel = $customerModel;
        $this->customerGroupModel = $customerGroupModel;
        $this->dateTime = $dateTime;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruVars = $sailthruVars;
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
     * @param MageCustomer $object
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function getCustomVariables($object, $data = [])
    {
        $dataModel = $object->getDataModel();
        $storeId = $dataModel->getStoreId();
        $store = $this->storeManager->getStore($storeId);
        $group = $this->customerGroupModel->load($dataModel->getGroupId());
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);

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
                $varKeys['firstname'] => $dataModel->getFirstname() ?? '',
                $varKeys['middlename'] => $dataModel->getMiddlename() ?? '',
                $varKeys['lastname'] => $dataModel->getLastname() ?? '',
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
     * @param string $param
     *
     * @return \Magento\Customer\Model\Customer
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getObject($param)
    {
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        try {
            $this->customerModel->loadByEmail($param);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "\n\n" . $e->getTraceAsString());
        }

        return $this->customerModel;
    }

    /**
     * To get Customer model.
     *
     * @param string|int $id
     *
     * @return \Magento\Customer\Model\Customer
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getObjectById($id)
    {
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        try {
            $this->customerModel->load($id);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "\n\n" . $e->getTraceAsString());
        }

        return $this->customerModel;
    }
}
