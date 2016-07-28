<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Sailthru\MageSail\Helper\Api;

class CartIntercept
{

    public function __construct(
        Api $sailthru, 
        ProductRepositoryInterface $productRepo, 
        Image $imageHelper, 
        Config $mediaConfig, 
        Product $productHelper, 
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $cpData,
        Configurable $configProduct,
       \Magento\Swatches\Block\Product\Renderer\Configurable $swatchModel
        ){
        $this->sailthru = $sailthru;
        $this->productRepo = $productRepo;
        $this->imageHelper = $imageHelper;
        $this->mediaConfig = $mediaConfig;
        $this->productHelper = $productHelper;
        $this->cpData = $cpData;
        $this->cpModel = $configProduct;
        $this->swatchModel = $swatchModel;
    }

    private function _gate($cart){
        if ($this->sailthru->isAbandonedCartEnabled()){
            return $this->sendCart($cart);
        } else {
            return $cart;
        }
    }

    protected function sendCart($cart){
        $customer = $cart->getCustomerSession()->getCustomer();
        $anonymousEmail = $this->isAnonymousReady();
        if ($customer or $anonymousEmail){
            $email = $anonymousEmail ? $anonymousEmail : $customer->getEmail(); 
            try {
                $this->sailthru->client->_eventType = "CartUpdate";
                $quote = $cart->getQuote();
                $items_visible = $quote->getAllVisibleItems();
                $items = $this->_getItems($items_visible);
                $data = [
                    'email'             => $email,
                    'items'             => $items,
                    'incomplete'        => 1,
                    'reminder_time'     => $this->sailthru->getAbandonedTime(),
                    'reminder_template' => $this->sailthru->getAbandonedTemplate(),
                    'message_id'        => $this->sailthru->getBlastId()
                ];
                $response = $this->sailthru->client->apiPost("purchase", $data);
            } catch(\Exception $e){
                $this->sailthru->logger($e);
                throw $e;
            } finally {
                return $cart;
            }
        }
        return $cart;
    }

    public function afterAddProductsByIds(Cart $cardModel, $cart){
        return $this->_gate($cart);
    }

    public function afterAddProduct(Cart $cartModel, $cart){
        return $this->_gate($cart);
    }

    public function afterRemoveItem(Cart $cartModel, $cart){
        return $this->_gate($cart);
    }

    public function afterTruncate(Cart $cardModel, $cart){
        return $this->_gate($cart);
    }

    public function afterUpdateItems(Cart $cardModel, $cart){
        return $this->_gate($cart);
    }

    public function isAnonymousReady(){
        if ($this->sailthru->canAbandonAnonymous() and $hid = $this->sailthru->getHid()){
            $response = $this->sailthru->client->getUserByKey($hid, 'cookie', array('keys' => 1));
            if (array_key_exists("keys", $response)){ 
                $email = $response["keys"]["email"];
                return $email;
            }
        }
        return false;
    }


    /**
     * Prepare data on items in cart or order.
     *
     * @return type
     */
    protected function _getItems($items)
    {
        try {
            $data = array();
            $configurableSkus = array();
            foreach($items as $item) {
                $product = $item->getProduct();
                $_item = array();
                $_item['vars'] = array();
                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $_item['isConfiguration'] = 1;
                    $parentIds[] = $item->getParentItemId();
                    // $options = $product->getTypeInstance(true)->getOrderOptions($item->getProduct());
                    $options = $this->cpModel->getOrderOptions($product);
                    $_item['id'] = $options['simple_sku'];
                    $_item['title'] = $options['simple_name'];
                    $_item['vars'] = $this->_getVars($options);
                    $configurableSkus[] = $options['simple_sku'];
                } elseif (!in_array($item->getSku(),$configurableSkus) && $item->getProductType() != 'bundle') {
                    $_item['id'] = $item->getSku();
                    $_item['title'] = $item->getName();
                } else {
                    $_item['id'] = null;
                }
                if ($_item['id']) {
                    $_item['qty'] = intval($item->getQty());
                    $_item['url'] = $item->getProduct()->getProductUrl();
                    $_item['image']=$this->productHelper->getSmallImageUrl($product);
                    $current_price = null;
                    $price_used = "reg";
                    $reg_price = $product->getPrice();
                    $special_price = $product->getSpecialPrice();
                    $special_from = $product->getSpecialFromDate();
                    $special_to = $product->getSpecialToDate();
                    if (!is_null($special_price) AND
                        (is_null($special_from) or (strtotime($special_from) < strtotime("Today"))) AND
                        (is_null($special_to) or (strtotime($special_to) > strtotime("Today")))) {
                        $current_price = $special_price;
                        $price_used = "special";
                    } else {
                        $current_price = $reg_price;
                    }
                    $_item['price'] = $current_price * 100;
                    if ($tags = $this->_getTags($product)) {
                        $_item['tags'] = $tags;
                    }
                    $data[] = $_item;
                }
            }
            return $data;
        } catch (\Exception $e) {
             $this->sailthru->logger($e);
             throw $e;
            return false;
        }
    }
    /**
     * Get order adjustments
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getAdjustments(Mage_Sales_Model_Order $order)
    {
        if ($order->getBaseDiscountAmount()) {
            return array(
                array(
                    'title' => 'Sale',
                    'price' => $order->getBaseDiscountAmount()
                )
            );
        }
        return array();
    }
    /**
     * Get payment information
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getTenders(Mage_Sales_Model_Order $order)
    {
        if ($order->getPayment()) {
           $tenders = array(
                        array(
                          'title' => $order->getPayment()->getCcType(),
                          'price' => $order->getPayment()->getBaseAmountOrdered()
                           )
                       );
            if ($tenders['title'] == null) {
                return '';
            }
            return $tenders;
        } else {
            return '';
        }
    }
    /**
     * Get product meta keywords
     * @param string $productId
     * @return string
     */
    protected function _getTags($product)
    {
        // return Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'meta_keyword', $this->_storeId);
        return $product->getData('meta_keyword');
    }
    
    
    /**
     *
     * @param array $options
     * @return array
     */
    protected function _getVars($options)
    {
        $vars = [];
        $data = $options['attributes_info'];
        foreach ($data as $attribute) {
            $vars[$attribute['label']] = $attribute['value'];
        }
        return $vars;
    }

}