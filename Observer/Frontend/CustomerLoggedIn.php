<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Customer\Model\Customer;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

class CustomerLoggedIn implements ObserverInterface
{

    const GET_FIELDS = [
        "keys" => 1,
        "lists" => 1,
        "optout_email" => 1
    ];

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
        /** @var Customer $customer */
        $customer = $observer->getData('customer');
        $storeId = $customer->getStore()->getId();
        $newsletterList = $this->sailthruSettings->getNewsletterList($storeId);
        $this->sailthruClient = $this->sailthruClient->getClient(true, $storeId);

        $sid = $customer->getData('sailthru_id');
        $email = $customer->getEmail();
        $data = [
            "id" => $sid ?: $email,
            "fields" => $this::GET_FIELDS
        ];

        try {
            $this->sailthruClient->_eventType = 'CustomerLogin';
            $response = $this->sailthruClient->apiGet('user', $data);
            $shouldUpdateSubscriptionStatus = $this->shouldUpdateSubscriptionStatus($newsletterList, $response);
            if ($shouldUpdateSubscriptionStatus) {
                $this->subscriber->loadByEmail($customer->getEmail());
                $this->subscriber->setSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED)->save();
            }
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
    private function shouldUpdateSubscriptionStatus($newsletterList, $userData)
    {
        return $userData['optout_email'] != 'none' || !in_array($newsletterList, array_keys($userData['lists']));
    }
}
