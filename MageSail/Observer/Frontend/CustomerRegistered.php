<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Cookie\Hid;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;


class CustomerRegistered implements ObserverInterface
{


    const SETTING_ENABLED = "magesail_lists/lists/enable_signup_list";

    const SETTING_VALUE = "magesail_lists/lists/signup_list";

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
        $this->moduleManager = $moduleManager;
        $this->sailthru = $sailthru;
    }


    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Sailthru_MageSail')) {

            $customer = $observer->getData('customer');

            try {
                $this->_eventType = 'CustomerRegister';
                $data = [
                        'id'     => $customer->getEmail(),
                        'key'    => 'email',
                        'fields' => [
                            'keys' => 1
                        ],
                        'vars'   => [
                            'firstName' => $customer->getFirstname(),
                            'lastName'  => $customer->getLastname(),
                            'name'  => "{$customer->getFirstname()} {$customer->getLastname()}"
                        ]
                ];

                if ($this->sailthru->getSettingsVal(self::SETTING_ENABLED)){
                    $list = $this->sailthru->getSettingsVal(self::SETTING_VALUE);
                    $data["lists"] = [ $list => 1 ];
                }
                $response = $this->sailthru->client->apiPost('user', $data);
                $this->sailthru->hid->set($response["keys"]["cookie"]);
                $this->sailthru->logger('SET COOKIE ORDER 67-------------------');

            } catch(\Sailthru_Email_Model_Client_Exception $e) {
                $this->sailthru->logger($e);
            } catch(\Exception $e) {
                $this->sailthru->logger($e);
            }
        }
    }
}
