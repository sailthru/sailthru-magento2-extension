<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use \Magento\Directory\Model\CountryFactory;
use Sailthru\MageSail\Logger;


class Shipment extends AbstractHelper
{
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        CountryFactory $countryFactory
    ) {
        parent::__construct($context, $storeManager, $logger);
        $this->countryFactory = $countryFactory;
    }

    /**
     * To get processed `shipment` variable.
     * 
     * @param  \Magento\Sales\Model\Order\Shipment  $shipment
     * @param  string                               $paymentHtml
     * @param  string                               $comment
     * 
     * @return array
     */
    public function getShipmentVars($shipment, $paymentHtml, $comment)
    {
        return [
            'shipment' => [
                'id' => $shipment->getIncrementId(),
                'items' => self::getShippingItems($shipment->getAllItems()),
                'created_date' => $shipment->getCreatedAt(),
                'trackingDetails' => self::getTrackingDetails($shipment),
                'shipmentItems' => self::getShippingItems($shipment->getAllItems()),
                'comment' => $comment,
                'paymentHtml' => $paymentHtml,
                'address' => self::getAddressVars($shipment),
            ],
        ];
    }

    /**
     * To get shipment items.
     * 
     * @param  array $items
     *
     * @return array
     */
    private function getShippingItems($items)
    {
        $itemsData = array();

        foreach($items as $item) {
            $item = $item->getOrderItem();
            $itemsData[] = array(
                "title" => $item->getName(),
                "options" => $item->getProductOptions(),
            );
        }

        return $itemsData;
    }

    /**
     * To get shipment tracking details.
     * 
     * @param  \Magento\Sales\Model\Order\Shipment  $shipment
     * 
     * @return array
     */
    private function getTrackingDetails($shipment)
    {
        $trackingDetails = [];
        $tracks = $shipment->getAllTracks();

        foreach ($tracks as $track) {
            $trackingDetails[] = [
                'by' => $track->getTitle(),
                'number' => $track->getNumber(),
            ];
        }

        return $trackingDetails;
    }

    /**
     * Prepare address data.
     * 
     * @param  \Magento\Sales\Model\Order\Shipment $shipment [description]
     * 
     * @return array
     */
    private function getAddressVars($shipment)
    {
        $address = $shipment->getShippingAddress();

        if ($address) {
            $streets = $address->getStreet();
            $country = $this->countryFactory->create()->loadByCode($address->getCountryId());

            return [
                'name' => $address->getName(),
                'company' => $address->getCompany(),
                'telephone' => $address->getTelephone(),
                'street1' => isset($streets[0]) ? $streets[0] : '',
                'street2' => isset($streets[1]) ? $streets[1] : '',
                'city' => $address->getCity(),
                'state' => $address->getRegion(),
                'state_code' => $address->getRegionCode(),
                'country' => $country->getName(),
                'country_code' => $address->getCountryId(),
                'postal_code' => $address->getPostcode(),
            ];
        }

        return [];
    }
}
