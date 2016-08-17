<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Cookie\Hid;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;

class CustomerLoggedIn implements ObserverInterface
{

    public function __construct(Api $sailthru)
    {
        $this->sailthru = $sailthru;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $sid = $customer->getData('sailthru_id');

        try {
            $this->sailthru->client->_eventType = 'CustomerLogin';
            $data = [
                    'id' => $sid ? $sid : $customer->getEmail(),
                    'fields' => [
                        'keys' => 1,
                        'engagement' => 1,
                        'activity' => 1,
                    ]
            ];
            $response = $this->sailthru->client->apiPost('user', $data);
            if (!$sid) {
                $customerData = $customer->getDataModel();
                $customerData->setCustomAttribute('sailthru_id', $response['keys']['sid']);
                $customer->updateData($customerData);
                $customer->save();
            }
            $this->sailthru->hid->set($response["keys"]["cookie"]);
        } catch (\Exception $e) {
            $this->sailthru->logger($e);
        }
    }
}
