<?php

namespace Sailthru\MageSail\Helper;

use Sailthru\MageSail\Helper\VariablesAbstractHelper;
use Magento\Framework\App\ObjectManager;

class Shipment extends VariablesAbstractHelper
{
    const SHIPMENT_MODEL = 'Magento\Sales\Model\Order\Shipment';

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
        return ObjectManager::getInstance()
            ->create(self::SHIPMENT_MODEL)
            ->loadByIncrementId($param);
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
