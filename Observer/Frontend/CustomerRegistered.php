<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\VarHelper;

class CustomerRegistered implements ObserverInterface
{

    private $sailthruClient, $sailthruSettings, $sailthruCookie, $sailthruVars;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        VarHelper $sailthruVars
    ) {
        $this->sailthruClient = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruVars = $sailthruVars;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $storeId = $customer->getStoreId();
        $this->sailthruClient = $this->sailthruClient->getClient(true, $storeId);
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
            $this->sailthruClient->_eventType = 'CustomerRegister';
            $response = $this->sailthruClient->apiPost('user', $data);
            $this->sailthruCookie->set($response["keys"]["cookie"]);
        } catch (\Sailthru_Client_Exception $e) {
            $this->sailthruClient->logger($e);
        }
    }
}
