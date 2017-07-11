<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerLoggedIn implements ObserverInterface
{

    private $sailthruCookie;
    private $sailthruClient;

    public function __construct(ClientManager $clientManager, SailthruCookie $sailthruCookie)
    {
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruClient = $clientManager->getClient();
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $sid = $customer->getData('sailthru_id');

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
}
