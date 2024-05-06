<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\VarHelper;

class CustomerRegistered implements ObserverInterface
{
    private $clientManager;
    private $sailthruSettings;
    private $sailthruCookie;
    private $sailthruVars;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        VarHelper $sailthruVars
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruVars = $sailthruVars;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $storeId = $customer->getStoreId();
        $client = $this->clientManager->getClient($storeId);
        $email = $customer->getEmail();
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);
        $data = [
            'id'     => $email,
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
