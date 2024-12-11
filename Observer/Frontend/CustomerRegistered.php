<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\VarHelper;
use Sailthru\MageSail\Logger;

class CustomerRegistered implements ObserverInterface
{
    private $clientManager;
    private $sailthruSettings;
    private $sailthruCookie;
    private $sailthruVars;
    private $logger;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        VarHelper $sailthruVars,
        Logger $logger
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruVars = $sailthruVars;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        try {
            $requestParams = $_REQUEST;
            $isSubscribed = isset($requestParams['is_subscribed']) ? 1 : 0;
            $optOutEmail = $isSubscribed == 1 ? "none" : "basic";

            $this->logger->info('############################ requestParams[\'is_subscribed\']): ' . isset($requestParams['is_subscribed']) .
                ' requestParams[\'is_subscribed\']: ' . (isset($requestParams['is_subscribed']) ? $requestParams['is_subscribed']: '0') . ' optOutEmail: ' . $optOutEmail);

            $this->logger->info('#############Request Params###############'. print_r($requestParams, true));
//            $this->logger->info('#############Customer Data###############'. print_r($customer, true));
        } catch (\Exception $e) {
            $this->logger->error("Error in CustomerRegistration # {$e->getMessage()}");
            $optOutEmail = "none";
        }
        $storeId = $customer->getStoreId();
        $client = $this->clientManager->getClient($storeId);
        $email = $customer->getEmail();
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);
        $data = [
            'id'     => $email,
            'optout_email' => $optOutEmail,
            'key'    => 'email',
            'fields' => [
                'keys' => 1
            ],
            'vars'   => [
                $varKeys['firstname'] => $customer->getFirstname(),
                $varKeys['lastname']  => $customer->getLastname(),
                'name'  => "{$customer->getFirstname()} {$customer->getLastname()}"
            ]
        ];
        if ($this->sailthruSettings->customerListEnabled($storeId)) {
            $list = $this->sailthruSettings->getCustomerList($storeId);
            $data["lists"] = [ $list => 1 ];
        }
        try {
            $client->_eventType = 'CustomerRegister';
            $response = $client->apiPost('user', $data);
            $this->sailthruCookie->set($response['keys']['cookie']);
        } catch (\Sailthru_Client_Exception $e) {
            $client->logger($e);
        }
    }
}
