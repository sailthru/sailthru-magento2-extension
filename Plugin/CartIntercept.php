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
    ) {
        $this->sailthru = $sailthru;
        $this->productRepo = $productRepo;
        $this->imageHelper = $imageHelper;
        $this->mediaConfig = $mediaConfig;
        $this->productHelper = $productHelper;
        $this->cpData = $cpData;
        $this->cpModel = $configProduct;
        $this->swatchModel = $swatchModel;
    }

    public function _gate($cart)
    {
        if ($this->sailthru->isAbandonedCartEnabled()) {
            return $this->sendCart($cart);
        } else {
            return $cart;
        }
    }

    public function sendCart($cart)
    {
        $customer = $cart->getCustomerSession()->getCustomer();
        $email = $customer->getEmail();
        
        if ($email or $anonymousEmail = $this->isAnonymousReady()) {
            $email = $email ? $email : $anonymousEmail;
            $quote = $cart->getQuote();
            
            // make sure bundle parts don't make it into data
            $items = $quote->getAllVisibleItems();
            foreach ($items as $index => $item) {
                if ($item->getParentItem()){
                    unset($items[$index]);
                }
            }

            $data = [
                'email'             => $email,
                'items'             => $this->_getItems($items),
                'incomplete'        => 1,
                'reminder_time'     => $this->sailthru->getAbandonedTime(),
                'reminder_template' => $this->sailthru->getAbandonedTemplate(),
                'message_id'        => $this->sailthru->getBlastId()
            ];
            try {
                $this->sailthru->client->_eventType = "CartUpdate";
                $response = $this->sailthru->client->apiPost("purchase", $data);
            } catch (\Exception $e) {
                $this->sailthru->logger($e);
                throw $e;
            } finally {
                return $cart;
            }
        }
        return $cart;
    }

    public function afterAddProductsByIds(Cart $cardModel, $cart)
    {
        return $this->_gate($cart);
    }

    public function afterAddProduct(Cart $cartModel, $cart)
    {
        return $this->_gate($cart);
    }

    public function afterRemoveItem(Cart $cartModel, $cart)
    {
        return $this->_gate($cart);
    }

    public function afterTruncate(Cart $cardModel, $cart)
    {
        return $this->_gate($cart);
    }

    public function afterUpdateItems(Cart $cardModel, $cart)
    {
        return $this->_gate($cart);
    }

    public function isAnonymousReady()
    {
        if ($this->sailthru->canAbandonAnonymous() and $hid = $this->sailthru->getHid()) {
            $response = $this->sailthru->client->getUserByKey($hid, 'cookie', ['keys' => 1]);
            if (array_key_exists("keys", $response)) {
                $email = $response["keys"]["email"];
                return $email;
            }
        }
        return false;
    }

    /**
     * Prepare data on items in cart or order.
     *
     * @return array|false
     */
    public function _getItems($items)
    {

        try {
            $data = [];
            $configurableSkus = [];
            foreach ($items as $item) {
                $product = $item->getProduct();
                $_item = [];
                $_item['vars'] = [];
                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $_item['isConfiguration'] = 1;
                    $parentIds[] = $item->getParentItemId();
                    $options = $this->cpModel->getOrderOptions($product);
                    $_item['id'] = $options['simple_sku'];
                    $_item['title'] = $options['simple_name'];
                    $_item['vars'] = $this->_getVars($options);
                    $configurableSkus[] = $options['simple_sku'];
                } else {
                    $_item['id'] = $item->getSku();
                    $_item['title'] = $item->getName();
                }
                if ($_item['id']) {
                    $_item['qty'] = (int) $item->getQty();
                    $_item['url'] = $item->getProduct()->getProductUrl();
                    $_item['image']=$this->productHelper->getSmallImageUrl($product);
                    $current_price = null;
                    $price_used = "reg";
                    $_item['price'] = $product->getFinalPrice() * 100;
                    // $special_price = $product->getSpecialPrice();
                    // $special_from = $product->getSpecialFromDate();
                    // $special_to = $product->getSpecialToDate();
                    // if ($special_price and
                    //     ($special_from === null or (strtotime($special_from) < strtotime("Today"))) and
                    //     ($special_to === null or (strtotime($special_to) > strtotime("Today")))) {
                    //     $current_price = $special_price;
                    //     $price_used = "special";
                    // } else {
                    //     $current_price = $reg_price;
                    // }
                    // $_item['price'] = $current_price * 100;
                    if ($tags = $this->_getTags($product)) {
                        $_item['tags'] = $tags;
                    }
                    $data[] = $_item;
                }
            }
            return $data;
        } catch (\Exception $e) {
            $this->sailthru->logger($e);
            return false;
        }
    }

    /**
     * Get product meta keywords
     * @param string $productId
     * @return string
     */
    public function _getTags($product)
    {
        return $product->getData('meta_keyword');
    }
    
    /**
     *
     * @param array $options
     * @return array
     */
    public function _getVars($options)
    {
        $vars = [];
        $data = $options['attributes_info'];
        foreach ($data as $attribute) {
            $vars[$attribute['label']] = $attribute['value'];
        }
        return $vars;
    }
}
