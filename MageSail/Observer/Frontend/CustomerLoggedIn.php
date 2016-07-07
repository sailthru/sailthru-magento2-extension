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
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Api
     */
    protected $sailthru;

    /**
     * Module manager
     *
     * @var Manager
     */
    private $moduleManager;


    public function __construct(Api $sailthru, Manager $moduleManager) {
        // $this->customerSession = $customerSession;
        $this->moduleManager = $moduleManager;
        $this->sailthru = $sailthru;
        // $this->_cookieManager = $cookieManager;
        // $this->_cookieMetadataFactory = $cookieMetadataFactory;
    }


    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Sailthru_MageSail')) {

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
                            'email' => $customer->getEmail(),
                            // 'extid' => $customer->getEntityId()
                        ]
                ];
                $response = $this->sailthru->client->apiPost('user', $data);
                if (!$sid){
                    $customerData = $customer->getDataModel();
                    $customerData->setCustomAttribute('sailthru_id', $response['keys']['sid']);
                    $customer->updateData($customerData);
                    $customer->save();
                    $this->sailthru->logger('ADDED SID TO PROFILE BITCH');
                }
                $this->sailthru->hid->set($response["keys"]["cookie"]);
                $this->sailthru->logger('SET COOKIE ORDER 66-------------------');

            } catch(\Sailthru_Email_Model_Client_Exception $e) {
                $this->sailthru->logger($e);
            } catch(\Exception $e) {
                $this->sailthru->logger($e);
            }
        }
    }
}
