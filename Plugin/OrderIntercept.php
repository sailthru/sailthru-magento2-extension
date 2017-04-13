<?php
/*
Purchase sync + Email Override
- Sync purchase
- Sendend normal email
*/

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigProduct;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender\Interceptor; // generated in var/generation. http://alanstorm.com/magento_2_object_manager_plugin_system
use Sailthru\MageSail\Helper\Api;

class OrderIntercept
{

    public function __construct(
        Api $sailthru,
        ProductRepositoryInterface $productRepo,
        Image $imageHelper,
        Config $mediaConfig,
        Product $productHelper,
        OrderResource $orderResource,
        ConfigProduct $cpModel
    ) {
        $this->sailthru      = $sailthru;
        $this->productRepo   = $productRepo;
        $this->imageHelper   = $imageHelper;
        $this->mediaConfig   = $mediaConfig;
        $this->productHelper = $productHelper;
        $this->orderResource = $orderResource;
        $this->cpModel       = $cpModel;
    }

    /**
     * Even if not using Sailthru to send the transactional, we want Sailthru to track the purchase.
    */
    public function aroundSend(Interceptor $subject, callable $proceed, Order $order, $syncVar = false)
    {
        $orderData = $this->getData($order);
        if ($this->sailthru->getOrderOverride()) {
            $template = $this->sailthru->getOrderTemplate();
            $alreadyOrdered = $order->getEmailSent();
            try {
                if (!$alreadyOrdered) {
                    $this->sendOrder($orderData, $template);
                    $order->setEmailSent(true);
                    $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                } else {
                    $this->sendCopy($orderData, $template);
                }
            } catch (\Exception $e) {
                $this->sailthru->logger($e->getMessage());
                throw new LocalizedException(new Phrase('Could not send purchase confirmation.'));
            }
        } else {
            $this->sendOrder($orderData, false);
            $val = $proceed($order);
            return $val;
        }
    }

    public function sendOrder($orderData, $template = null)
    {
        try {
            $this->sailthru->client->_eventType = 'placeOrder';
            if ($template) {
                $orderData['send_template'] = $template;
            }
            $response = $this->sailthru->client->apiPost('purchase', $orderData);
            if (array_key_exists('error', $response)) {
                throw new LocalizedException(new Phrase($response['error']));
            }
            $hid = $this->sailthru->hid->get();
            if (!$hid) {
                $response = $this->sailthru->client->apiGet(
                    'user',
                    [
                        'id' => $orderData['email'],
                        'fields' => ['keys'=>1]
                    ]
                );
                if (isset($response['keys']['cookie'])) {
                    $this->sailthru->hid->set($response['keys']['cookie']);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sendCopy($orderData, $template)
    {
        $this->sailthru->client->_eventType = 'orderEmail';
        $sendData = [
            'email' => $orderData['email'],
            'vars' => $orderData['vars'],
            'template' => $template,
        ];
        unset($orderData['email']);
        unset($orderData['vars']);
        $sendData['vars'] += $orderData;
        try {
            $response = $this->sailthru->client->apiPost('send', $sendData);
            if (array_key_exists('error', $response)) {
                throw new LocalizedException(new Phrase($response['error']));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getData($order)
    {
            return [
                'email'       => $email = $order->getCustomerEmail(),
                'items'       => $this->processItems($order->getAllVisibleItems()),
                'adjustments' => $adjustments = $this->processAdjustments($order),
                'vars'        => $this->getOrderVars($order, $adjustments),
                'message_id'  => $this->sailthru->getBlastId(),
                'tenders'     => $this->processTenders($order),
            ];
    }

    /**
     * Prepare data on items in cart or order.
     *
     * @return type
     */
    public function processItems($items)
    {
        try {
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
                } elseif (!in_array($item->getSku(), $configurableSkus)) {
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
                    if ($tags = $this->sailthru->getTags($product)) {
                        $_item['tags'] = $tags;
                    }
                    $data[] = $_item;
                }
            }
            return $data;
        } catch (\Exception $e) {
            $this->sailthru->logger($e);
            throw $e;
        }
    }
    /**
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function processAdjustments(Order $order)
    {
        $adjustments = [];
        if ($shipCost = $order->getShippingAmount()) {
            $adjustments[] = [
                'title' => 'Shipping',
                'price' => $shipCost*100,
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
                'price' => $tax*100,
            ];
        }
        return $adjustments;
    }
    /**
     * Get payment information
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function processTenders(Order $order)
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
        } else {
            return '';
        }
    }
    
    /**
     *
     * @param array $options
     * @return array
     */
    public function getItemVars($options)
    {
        $vars = [];
        if (array_key_exists('attributes_info', $options)) {
            foreach ($options['attributes_info'] as $attribute) {
                $vars[$attribute['label']] = $attribute['value'];
            }
        }
        return $vars;
    }

    public function getOrderVars($order, $adjustments)
    {
        $vars = [];
        foreach ($adjustments as $adj) {
            $vars[$adj['title']] =  $adj['price'];
        }
        $vars['orderId'] = $order->getId();
        return $vars;
    }
}
