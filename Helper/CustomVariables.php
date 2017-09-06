<?php

namespace Sailthru\MageSail\Helper;

use Magento\Customer\Model\Customer as MageCustomer;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

class CustomVariables extends AbstractHelper
{
    /**
     * To get array with additional variables.
     * 
     * @param  array  $data
     * 
     * @return array  $variables
     */
    public function getVariables($data)
    {
        switch ($data['type']) {
            case 'customer':
                $variables = $this->getCustomerVariables($data['object']);
                break;

            case 'order':
                $variables = $this->getOrderVariables($data['object']);
                break;

            case 'shipment':
                $variables = $this->getShipmentVariables($data['object'], $data['paymentHtml'], $data['comment']);
                break;

            case 'isGuest':
                $variables = $this->getIsGuest($data['object'], $data['objectType']);
                break;
            
            default:
                $variables = [];
                break;
        }

        return $variables;
    }
    
    /**
     * To get Customer additional variables.
     * 
     * @param  MageCustomer $customerModel
     * 
     * @return array
     */
    public function getCustomerVariables(MageCustomer $customerModel)
    {        
        $data = $customerModel->getDataModel();
        $store = $this->storeManager->getStore($data->getStoreId());
        $group = ObjectManager::getInstance()->create('Magento\Customer\Model\Group')->load($data->getGroupId());

        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $data->getCreatedAt(),
            new \DateTimeZone(
                ObjectManager::getInstance()->create('Magento\Framework\Stdlib\DateTime\Timezone')->getConfigTimezone()
            )
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
                'defaultBillingAddress' => $this->getAddress($customerModel->getDefaultBillingAddress()),
                'defaultShippingAddress' => $this->getAddress($customerModel->getDefaultShippingAddress()),
            ],
            'registration' => [
                'name' => $customerModel->getName(),
            ],
        ];
    }

    /**
     * To get processed order variable.
     * 
     * @param  Order  $order
     * 
     * @return array
     */
    public function getOrderVariables(Order $order)
    {        
        return [
            'order' => [
                'id' => $order->getId(),
                'items' => $this->getOrderItems($order),
                'adjustments' => $this->getOrderAdjustments($order),
                'tenders' => $this->getOrderTenders($order),
                'name' => $order->getCustomerName(),
                'status' => $order->getStatus(),
                'state' => $order->getState(),
                'created_date' => $order->getCreatedAt(),
                'total' => $order->getGrandTotal(),
                'subtotal' => $order->getSubtotal(),
                'couponCode' => $order->getCouponCode(),
                'discount' => $order->getDiscountAmount(),
                'shippingDescription' => $order->getShippingDescription(),
                'isGuest' => $order->getCustomerIsGuest() ? 1 : 0,
                'billingAddress' => $this->getAddress($order->getBillingAddress(), true),
                'shippingAddress' => $this->getAddress($order->getShippingAddress(), true),
            ],
        ];
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
    public function getShipmentVariables($shipment, $paymentHtml, $comment)
    {
        return [
            'shipment' => [
                'id' => $shipment->getIncrementId(),
                'items' => $this->getShippingItems($shipment->getAllItems()),
                'created_date' => $shipment->getCreatedAt(),
                'trackingDetails' => $this->getTrackingDetails($shipment),
                'shipmentItems' => $this->getShippingItems($shipment->getAllItems()),
                'comment' => $comment,
                'paymentHtml' => $paymentHtml,
                'address' => $this->getAddress($shipment->getShippingAddress()),
            ],
        ];
    }

    /**
     * To get `isGuest` variable for order\shipment.

     * @param  mixed  $object
     * @param  string $type
     *
     * @return array
     */
    public function getIsGuest($object, $type)
    {
        if ('order' == $type) {
            $isGuest = $object->getCustomerIsGuest() ? 1 : 0;
        } else {
            $isGuest = $object->getOrder()->getCustomerIsGuest() ? 1 : 0;
        }

        return ['isGuest' => $isGuest];
    }

    /**
     * To get formatted address information.
     * 
     * @param  Magento\Customer\Model\Address $address
     * @param  bool                           $useFullAddress
     * 
     * @return array
     */
    public function getAddress($address, $useFullAddress = false)
    {
        if ($address) {
            $streets = $address->getStreet();

            $countryId = in_array('getCountryId', get_class_methods($address))
                ? $address->getCountryId()
                : $address->getCountry();

            $countryInfo = ObjectManager::getInstance()
                ->create('Magento\Directory\Api\CountryInformationAcquirerInterface')
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
                    'name' => !empty($address->getName()) ? $address->getName() : '',
                    'company' => !empty($address->getCompany()) ? $address->getCompany() : '',
                    'telephone' => !empty($address->getTelephone()) ? $address->getTelephone() : '',
                    'street1' => isset($streets[0]) ? $streets[0] : '',
                    'street2' => isset($streets[1]) ? $streets[1] : '',
                ];
            }

            return $addressInfo;
        }

        return [];
    }

    /**
     * Prepare data on items in order.
     *
     * @param  Order $order
     * 
     * @return array
     */
    private function getOrderItems(Order $order)
    {
        /** @var \Magento\Sales\Model\Order\Item[] $items */
        $items = $order->getAllVisibleItems();
        $data = [];
        $configurableSkus = [];

        foreach ($items as $item) {
            $product = $item->getProduct();
            $_item = [];
            $_item['vars'] = [];

            if ($item->getProduct()->getTypeId() == 'configurable') {
                $parentIds[] = $item->getParentItemId();
                $options = $item->getProductOptions();
                $_item['id'] = $options['simple_sku'];
                $_item['title'] = $options['simple_name'];
                $_item['vars'] = $this->getItemVars($options);
                $configurableSkus[] = $options['simple_sku'];
            } elseif (!in_array($item->getSku(), $configurableSkus) && $item->getProductType() != 'bundle') {
                $_item['id'] = $item->getSku();
                $_item['title'] = $item->getName();
            } else {
                $_item['id'] = null;
            }

            if ($_item['id']) {
                $_item['qty'] = $item->getQtyOrdered();
                $_item['url'] = $item->getProduct()->getProductUrl();
                $_item['image'] = ObjectManager::getInstance()
                                    ->create('Magento\Catalog\Helper\Product')
                                    ->getSmallImageUrl($product);
                $_item['price'] = $item->getPrice() * 100;

                if ($tags = ObjectManager::getInstance()
                        ->create('Sailthru\MageSail\Helper\Product')
                        ->getTags($product)) {
                    $_item['tags'] = $tags;
                }

                $data[] = $_item;
            }
        }

        return $data;
    }

    /**
     * Get order adjustments.
     * 
     * @param  Order $order
     * 
     * @return array
     */
    private function getOrderAdjustments(Order $order)
    {
        $adjustments = [];

        if ($shipCost = $order->getShippingAmount()) {
            $adjustments[] = [
                'title' => 'Shipping',
                'price' => $shipCost * 100,
            ];
        }

        if ($discount = $order->getDiscountAmount()) {
            $adjustments[] = [
                'title' => 'Discount',
                'price' => (($discount > 0) ? $discount*-1 : $discount)*100,
            ];
        }

        if ($tax = $order->getTaxAmount()) {
            $adjustments[] = [
                'title' => 'Tax',
                'price' => $tax * 100,
            ];
        }

        return $adjustments;
    }

    /**
     * Get payment information.
     * 
     * @param  Order $order
     * 
     * @return mixed
     */
    private function getOrderTenders(Order $order)
    {
        if ($order->getPayment()) {
            $tenders = [
                [
                  'title' => $order->getPayment()->getCcType(),
                  'price' => $order->getPayment()->getBaseAmountOrdered()
                ]
            ];
            if ($tenders[0]['title'] == null) {
                return '';
            }
            return $tenders;
        }

        return '';
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
     * Get Sailthru item object vars.
     * 
     * @param  array $options
     * 
     * @return array
     */
    private function getItemVars($options)
    {
        $vars = [];
        if (array_key_exists('attributes_info', $options)) {
            foreach ($options['attributes_info'] as $attribute) {
                $vars[$attribute['label']] = $attribute['value'];
            }
        }

        return $vars;
    }
}
