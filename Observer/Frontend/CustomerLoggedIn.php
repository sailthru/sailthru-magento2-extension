<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Customer\Model\Customer;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Logger;

class CustomerLoggedIn implements ObserverInterface
{

    private $sailthruCookie;
    private $clientManager;
    private $logger;

    public function __construct(
        ClientManager $clientManager,
        SailthruCookie $sailthruCookie,
        Logger $logger
    ) {
        $this->sailthruCookie = $sailthruCookie;
        $this->clientManager = $clientManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getData('customer');
        $this->logger->info("customer", $customer->debug());
        $client = $this->clientManager->getClient(true, $customer->getStore()->getId());
        $sid = $customer->getData('sailthru_id');
        $client->_eventType = 'CustomerLogin';
        $data = [
            'id' => $sid ? $sid : $customer->getEmail(),
            'fields' => [
                'keys' => 1,
                'engagement' => 1,
                'activity' => 1,
            ]
        ];
        try {
            $response = $client->apiPost('user', $data);
            if (!$sid) {
                $customerData = $customer->getDataModel();
                $customerData->setCustomAttribute('sailthru_id', $response['keys']['sid']);
                $customer->updateData($customerData);
                $customer->save();
            }
            $this->sailthruCookie->set($response["keys"]["cookie"]);
        } catch (\Exception $e) {
            $this->logger->err("Exception logging in user with Sailthru: {$e->getMessage()}", ["exception" => $e]);
        }
    }
}
