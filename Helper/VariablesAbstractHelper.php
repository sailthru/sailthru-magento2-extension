<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Magento\Framework\App\Helper\AbstractHelper as MageAbstractHelper;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

abstract class VariablesAbstractHelper extends MageAbstractHelper
{
    /** @var Magento\Directory\Api\CountryInformationAcquirerInterface */
    protected $countryInformation;
    
    /** @var StoreManager  */
    protected $storeManager;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        CountryInformationAcquirerInterface $countryInformation
    ) {
        parent::__construct($context);

        $this->countryInformation = $countryInformation;
        $this->storeManager = $storeManager;
    }

    /**
     * @param  mixed $object
     * @param  array $data
     */
    abstract public function getCustomVariables($object, $data = []);

    /**
     * @param  string|int $param
     */
    abstract public function getObject($param);

    /**
     * To get formatted address information.
     * 
     * @param  mixed  $address
     * @param  bool   $useFullAddress
     * 
     * @return array
     */
    public function getAddress($address, $useFullAddress = false)
    {
        if (!$address) {
            return [];
        }

        $streets = $address->getStreet();
        $countryId = in_array('getCountryId', get_class_methods($address))
            ? $address->getCountryId()
            : $address->getCountry();

        $countryInfo = $this->countryInformation->getCountryInfo($countryId);

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
