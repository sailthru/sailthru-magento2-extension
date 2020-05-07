<?php

namespace Sailthru\MageSail\Helper;

use Sailthru\MageSail\Helper\VariablesAbstractHelper;
use Magento\Sales\Model\Order\Shipment as ShipmentModel;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

class Shipment extends VariablesAbstractHelper
{
    /** @var Magento\Sales\Model\Order\Shipment */
    private $shipmentModel;

    public function __construct(
        ShipmentModel $shipmentModel,
        Context $context,
        StoreManager $storeManager,
        CountryInformationAcquirerInterface $countryInformation
    ) {
        parent::__construct($context, $storeManager, $countryInformation);

        $this->shipmentModel = $shipmentModel;
    }

    /**
     * To get processed `shipment` variable.
     * 
     * @param  \Magento\Sales\Model\Order\Shipment  $object
     * @param  array                                $data
     * 
     * @return array
     */
    public function getCustomVariables($object, $data = [])
    {
        return [
            'shipment' => [
                'id' => $object->getIncrementId(),
                'items' => $this->getShippingItems($object->getAllItems()),
                'created_date' => $object->getCreatedAt(),
                'trackingDetails' => $this->getTrackingDetails($object),
                'shipmentItems' => $this->getShippingItems($object->getAllItems()),
                'comment' => $data['comment'] ?? '',
                'paymentHtml' => $data['paymentHtml'] ?? '',
                'address' => $this->getAddress($object->getShippingAddress()),
            ],
        ];
    }

    /**
     * To get Shipment model.
     * 
     * @param  string $param
     * 
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getObject($param)
    {
        return $this->shipmentModel->loadByIncrementId($param);
    }

    /**
     * To get `isGuest` attribute for given Shipment object.
     * 
     * @param  \Magento\Sales\Model\Order\Shipment $shipment
     * 
     * @return array
     */
    public function getIsGuestVariable($shipment)
    {
        return ['isGuest' => $shipment->getOrder()->getCustomerIsGuest() ? 1 : 0];
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
        $itemsData = [];

        foreach($items as $item) {
            $item = $item->getOrderItem();
            if (empty($item)) {
                continue;
            }
            $itemsData[] = [
                "title" => $item->getName(),
                "options" => $item->getProductOptions(),
            ];
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
}
