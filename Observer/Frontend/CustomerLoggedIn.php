<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

class CustomerLoggedIn implements ObserverInterface
{

    private $sailthruCookie;
    private $sailthruClient;
    private $sailthruSettings;
    private $subscriber;

    public function __construct(
        ClientManager $clientManager,
        SailthruCookie $sailthruCookie,
        SailthruSettings $sailthruSettings,
        Subscriber $subscriber
    ) {
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruClient = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->subscriber = $subscriber;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $storeId = $customer->getStore()->getId();
        $this->sailthruClient = $this->sailthruClient->getClient(true, $storeId);
        $sid = $customer->getData('sailthru_id');
        $newsletterList = $this->sailthruSettings->getNewsletterList($storeId);

        try {
            $this->sailthruClient->_eventType = 'CustomerLogin';
            $data = [
                    'id' => $sid ? $sid : $customer->getEmail(),
                    'fields' => [
                        'keys' => 1,
                        'engagement' => 1,
                        'activity' => 1,
                    ]
            ];
            $shouldUpdate = $this->shouldUpdate($sid);
            if ($shouldUpdate) {
                $this->subscriber->loadByEmail($customer->getEmail());
                $this->subscriber->setSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED)->save();
            }
            $response = $this->sailthruClient->apiPost('user', $data);
            if (!$sid) {
                $customerData = $customer->getDataModel();
                $customerData->setCustomAttribute('sailthru_id', $response['keys']['sid']);
                $customer->updateData($customerData);
                $customer->save();
            }
            $this->sailthruCookie->set($response["keys"]["cookie"]);
        } catch (\Exception $e) {
            $this->sailthruClient->logger($e);
        }
    }
    private function shouldUpdate($sid)
    {
        $userData = $this->sailthruClient->apiGet('user', [ 'id' => $sid  ]);
        return $userData['optout_email'] != 'none' || $this->subscriber->getStatus() == Subscriber::STATUS_UNSUBSCRIBED;
    }
}
