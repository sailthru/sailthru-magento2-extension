<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Cookie\Hid;
use Magento\Customer\Model\Session;
use Magento\CUstomer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;


class CustomerAccountEdit implements ObserverInterface
{


    const SETTING_ENABLED = "magesail_lists/lists/enable_signup_list";

    const SETTING_VALUE = "magesail_lists/lists/signup_list";

    /**
     * @var Session
     */
    protected $customerModel;

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


    public function __construct(Api $sailthru, Manager $moduleManager, Customer $customerModel) {
        $this->moduleManager = $moduleManager;
        $this->sailthru = $sailthru;
        $this->customerModel = $customerModel;
    }


    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Sailthru_MageSail')) {

            $email = $observer->getData('email');
            $customer = $this->customerModel->loadByEmail($email);

            try {
                $this->sailthru->client->_eventType = 'update';

                $data = [
                        'id' => $email,
                        'key' => 'email',
                        'fields' => ['keys' => 1], 
                        'vars' => []
                ];

                $response = $this->sailthru->client->apiPost('user', $data);
                $this->sailthru->hid->set($response["keys"]["cookie"]);
                $this->sailthru->logger('PROFILE UPDATE-------------------');

            } catch(Sailthru_Email_Model_Client_Exception $e) {
                $this->sailthru->logger($e);
            } catch(Exception $e) {
                $this->sailthru->logger($e);
            }
        }
    }
}
