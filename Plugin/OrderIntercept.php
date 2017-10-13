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
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender\Interceptor; // generated in var/generation. http://alanstorm.com/magento_2_object_manager_plugin_system
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\ProductData as SailthruProduct;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;

class SaveOrder implements ObserverInterface;
{
    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruProduct $sailthruProduct,
        SailthruCookie $sailthruCookie,
        ProductRepositoryInterface $productRepo,
        Image $imageHelper,
        Config $mediaConfig,
        Product $productHelper,
        OrderResource $orderResource,
        ConfigProduct $cpModel
    ) {
        $this->sailthruClient   = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruProduct  = $sailthruProduct;
        $this->sailthruCookie   = $sailthruCookie;
        $this->productRepo      = $productRepo;
        $this->imageHelper      = $imageHelper;
        $this->mediaConfig      = $mediaConfig;
        $this->productHelper    = $productHelper;
        $this->orderResource    = $orderResource;
        $this->cpModel          = $cpModel;
    }

    /**
     * Even if not using Sailthru to send the transactional, we want Sailthru to track the purchase.
    */
    public function aroundSend(Interceptor $subject, callable $proceed, Order $order, $syncVar = false)
    {
        $orderData = $this->getData($order);
        $entityStoreId = $order->getStoreId();
        $this->sailthruClient = $this->sailthruClient->getClient(true, $entityStoreId);
        if ($this->sailthruSettings->getOrderOverride($entityStoreId)) {
            $template = $this->sailthruSettings->getOrderTemplate($entityStoreId);
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
                $this->sailthruClient->logger($e->getMessage());
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
            $this->sailthruClient->_eventType = 'placeOrder';
            if ($template) {
                $orderData['send_template'] = $template;
            }
            $response = $this->sailthruClient->apiPost('purchase', $orderData);
            if (array_key_exists('error', $response)) {
                throw new LocalizedException(new Phrase($response['error']));
            }
            $hid = $this->sailthruCookie->get();
            if (!$hid) {
                $response = $this->sailthruClient->apiGet(
                    'user',
                    [
                        'id' => $orderData['email'],
                        'fields' => ['keys'=>1]
                    ]
                );
                if (isset($response['keys']['cookie'])) {
                    $this->sailthruCookie->set($response['keys']['cookie']);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sendCopy($orderData, $template)
    {
        $this->sailthruClient->_eventType = 'orderEmail';
        $sendData = [
            'email' => $orderData['email'],
            'vars' => $orderData['vars'],
            'template' => $template,
        ];
        unset($orderData['email']);
        unset($orderData['vars']);
        $sendData['vars'] += $orderData;
        try {
            $response = $this->sailthruClient->apiPost('send', $sendData);
            if (array_key_exists('error', $response)) {
                throw new LocalizedException(new Phrase($response['error']));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
