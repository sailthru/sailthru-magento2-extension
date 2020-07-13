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
        'keys'         => 1,
        'lists'        => 1,
        'optout_email' => 1
    ];

    private $sailthruCookie;
    private $clientManager;
    private $sailthruSettings;
    private $subscriber;

    public function __construct(
        ClientManager $clientManager,
        SailthruCookie $sailthruCookie,
        SailthruSettings $sailthruSettings,
        Subscriber $subscriber
    ) {
        $this->sailthruCookie = $sailthruCookie;
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->subscriber = $subscriber;
    }

    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getData('customer');
        $storeId = $customer->getStore()->getId();
        $newsletterList = $this->sailthruSettings->getNewsletterList($storeId);
        $client = $this->clientManager->getClient($storeId);

        $sid = $customer->getData('sailthru_id');
        $email = $customer->getEmail();
        $data = [
            'id'     => $sid ? : $email,
            'fields' => $this::GET_FIELDS
        ];

        try {
            $client->_eventType = 'CustomerLogin';
            $response = $client->apiGet('user', $data);
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
            $this->sailthruCookie->set($response['keys']['cookie']);
        } catch (\Exception $e) {
            $client->logger($e);
        }
    }

    /**
     * Should update subscription status
     *
     * @param $newsletterList
     * @param $userData
     *
     * @return bool
     */
    protected function shouldUpdateSubscriptionStatus($newsletterList, $userData)
    {
        if (!isset($userData['optout_email']) || empty($newsletterList) || empty($userData['lists'])) {
            return false;
        }

        return $userData['optout_email'] != 'none' || !in_array($newsletterList, array_keys($userData['lists']));
    }
}
