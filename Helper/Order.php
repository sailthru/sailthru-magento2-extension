<?php

namespace Sailthru\MageSail\Helper;

use Magento\Sales\Model\Order as OrderModel;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManager;
use \Magento\Directory\Model\CountryFactory;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Helper\Product as SailthruProduct;


class Order extends AbstractHelper
{
    /**
     * @var SailthruProduct
     */
    protected $sailthruProduct;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        \Magento\Catalog\Helper\Product $productHelper,
        SailthruProduct $sailthruProduct,
        CountryFactory $countryFactory
    ) {
        parent::__construct($context, $storeManager, $logger);
        
        $this->productHelper = $productHelper;
        $this->sailthruProduct = $sailthruProduct;
        $this->countryFactory = $countryFactory;
    }

    /**
     * To get processed order variable.
     * 
     * @param  OrderModel  $order
     * 
     * @return array
     */
    public function getOrderVars(OrderModel $order)
    {
        return [
            'order' => [
                'id' => $order->getId(),
                'items' => self::processItems($order),
                'adjustments' => self::processAdjustments($order),
                'tenders' => self::processTenders($order),
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
                'billingAddress' => self::processAddress($order, 'billing'),
                'shippingAddress' => self::processAddress($order, 'shipping'),
            ],
        ];
    }

    /**
     * Prepare address data.
     * 
     * @param  OrderModel $order
     * @param  string     $type
     * 
     * @return array
     */
    private function processAddress(OrderModel $order, $type)
    {
        $address = 'billing' == $type
            ? $order->getBillingAddress()
            : $order->getShippingAddress();

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


    /**
     * Prepare data on items in order.
     *
     * @param OrderModel $order
     * 
     * @return array
     */
    private function processItems(OrderModel $order)
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
                $_item['image']=$this->productHelper->getSmallImageUrl($product);
                $_item['price'] = $item->getPrice() * 100;

                if ($tags = $this->sailthruProduct->getTags($product)) {
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
     * @param OrderModel $order
     * 
     * @return array
     */
    private function processAdjustments(OrderModel $order)
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
     * @param OrderModel $order
     * 
     * @return mixed
     */
    private function processTenders(OrderModel $order)
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
     * Get Sailthru item object vars.
     * 
     * @param array $options
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
