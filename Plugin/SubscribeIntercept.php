<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;

class SubscribeIntercept
{

    public function __construct(ClientManager $clientManager, SailthruSettings $sailthruSettings)
    {
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
    }

    /**
     * Saving customer subscription status
     *
     * @param generic Subscriber Model $subscriberModel
     * @param loaded Subscriber $subscriber
     * @return  $subscriber
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */


    public function afterSave(Subscriber $subscriberModel, $subscriber)
    {
        $this->updateSailthruSubscription($subscriber);
        return $subscriber;
    }

    /**
     * Saving customer unsubscribe status through FrontEnd Control Panel
     *
     * @param generic Subscriber Model $subscriberModel
     * @param loaded Subscriber $subscriber
     * @return  $subscriber
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterUnsubscribeCustomerById(Subscriber $subscriberModel, Subscriber $subscriber)
    {
        $this->updateSailthruSubscription($subscriber);
        return $subscriber;
    }

    public function updateSailthruSubscription(Subscriber $subscriber)
    {
        $email = $subscriber->getEmail();
        $status = $subscriber->getStatus();
        $isSubscribed = ($status == Subscriber::STATUS_SUBSCRIBED ? 1 : 0);

        $newsletter_enabled = $this->sailthruSettings->getSettingsVal(SailthruSettings::XML_NEWSLETTER_LIST_ENABLED);
        $newsletter_list    = $this->sailthruSettings->getSettingsVal(SailthruSettings::XML_NEWSLETTER_LIST_VALUE);

        if (($status == Subscriber::STATUS_UNSUBSCRIBED or $status == Subscriber::STATUS_SUBSCRIBED)
            and $newsletter_list and $newsletter_enabled) {

            try {
                $this->client->_eventType = $isSubscribed ? 'CustomerSubscribe' : 'CustomerUnsubscribe';
                $data = [
                        'id'     => $email,
                        'key'    => 'email',
                        'lists'  => [ $newsletter_list => $isSubscribed ],
                ];
                if ($fullName = $subscriber->getSubscriberFullName()) {
                    $data['vars'] = [
                        'firstName' => $subscriber->getFirstname(),
                        'lastName'  => $subscriber->getLastname(),
                        'name'      => $fullName,
                    ];
                }
                $response = $this->client->apiPost('user', $data);
                if (array_key_exists("error", $response)) {
                    $this->client->logger($response["errormsg"]);
                    throw new \Exception($response['errormsg']);
                }
            } catch (\Exception $e) {
                $this->client->logger($e->getMessage());
                throw new LocalizedException(__('We were unable to subscribe the customer.'));
            }
        }
    }
}
