<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;

abstract class VariablesAbstractHelper extends MageAbstractHelper
{
    const COUNTRY_INFORMATION = 'Magento\Directory\Api\CountryInformationAcquirerInterface';
    
    /** @var StoreManager  */
    protected $storeManager;

    public function __construct(Context $context, StoreManager $storeManager) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * @param  mixed $object
     * @param  array $data
     */
    public abstract function getCustomVariables($object, $data = []);

    /**
     * @param  mixed $param
     */
    public abstract function getObject($param);

    /**
     * To get formatted address information.
     * 
     * @param  mixec  $address
     * @param  bool   $useFullAddress
     * 
     * @return array
     */
    public function getAddress($address, $useFullAddress = false)
    {
        if (!$address)
            return [];

        $streets = $address->getStreet();
        $countryId = in_array('getCountryId', get_class_methods($address))
            ? $address->getCountryId()
            : $address->getCountry();

        $countryInfo = ObjectManager::getInstance()
            ->create(self::COUNTRY_INFORMATION)
            ->getCountryInfo($countryId);

        $addressInfo = [
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'state_code' => $address->getRegionCode(),
            'country' =>  $countryInfo->getFullNameLocale(),
            'country_code' => $countryId,
            'postal_code' => $address->getPostcode(),
        ];

        if ($useFullAddress) {
            $addressInfo += [
                'name' => $address->getName() ?? '',
                'company' => $address->getCompany() ?? '',
                'telephone' => $address->getTelephone() ?? '',
                'street1' => $streets[0] ?? '',
                'street2' => $streets[1] ?? '',
            ];
        }

        return $addressInfo;
    }
}
