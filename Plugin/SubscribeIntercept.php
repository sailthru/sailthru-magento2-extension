<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\ScopeResolver;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\VarHelper;

class SubscribeIntercept
{
    protected static $UPDATE_STATUSES = [
        Subscriber::STATUS_UNSUBSCRIBED,
        Subscriber::STATUS_SUBSCRIBED
    ];

    /** @var ClientManager  */
    private $clientManager;

    /** @var SailthruSettings  */
    private $sailthruSettings;

    /** @var StoreManagerInterface */
    private $storeManager;

    private $sailthruVars;

    /** @var ScopeResolver  */
    protected $scopeResolver;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        StoreManagerInterface $storeManager,
        ScopeResolver $scopeResolver,
        VarHelper $sailthruVars
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->storeManager = $storeManager;
        $this->scopeResolver = $scopeResolver;
        $this->sailthruVars = $sailthruVars;
    }

    /**
     * Saving customer subscription status
     *
     * @param Subscriber Model $subscriberModel (generic)
     * @param Subscriber $subscriber (loaded)
     * @return  $subscriber
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws \Exception
     */
    public function afterSave(Subscriber $subscriberModel, Subscriber $subscriber)
    {
        $this->updateSailthruSubscription($subscriber);
        return $subscriber;
    }

    /**
     * Saving customer unsubscribe status through FrontEnd Control Panel
     *
     * @param Subscriber $subscriberModel
     * @param Subscriber $subscriber
     * @return Subscriber $subscriber
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterUnsubscribeCustomerById(Subscriber $subscriberModel, Subscriber $subscriber)
    {
        $this->updateSailthruSubscription($subscriber);
        return $subscriber;
    }

    /**
     * @param Subscriber $subscriber
     * @throws \Exception
     */
    public function updateSailthruSubscription(Subscriber $subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $email = $subscriber->getEmail();
        $status = $subscriber->getStatus();
        $isSubscribed = ($status == Subscriber::STATUS_SUBSCRIBED ? 1 : 0);
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);

        if ($this->shouldUpdate($status, $storeId)) {
            $newsletterList = $this->sailthruSettings->getNewsletterList($storeId);
            $data = [
                'id'     => $email,
                'key'    => 'email',
                'lists'  => [ $newsletterList => $isSubscribed ],
            ];

            if ($fullName = $subscriber->getSubscriberFullName()) {
                $data['vars'] = [
                    $varKeys['firstname'] => $subscriber->getFirstname(),
                    $varKeys['lastname']  => $subscriber->getLastname(),
                    'name'      => $fullName,
                ];
            }

            $client = $this->clientManager->getClient($storeId);
            $client->_eventType = $isSubscribed ? 'CustomerSubscribe' : 'CustomerUnsubscribe';
            try {
                $client->apiPost('user', $data);
            } catch(\Sailthru_Client_Exception $e) {
                $client->logger($e->getMessage());
                throw new \Exception($e->getMessage());
            }
        }
    }

    private function shouldUpdate($status, $storeId)
    {
        return in_array($status, self::$UPDATE_STATUSES) and $this->sailthruSettings->newsletterListEnabled($storeId);
    }
}
