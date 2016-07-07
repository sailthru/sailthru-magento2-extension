<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Cookie\Hid;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\Newsletter\Model\Subscriber;


class CustomerRegistered implements ObserverInterface
{


    const XML_ONREGISTER_LIST_ENABLED = "magesail_lists/lists/enable_signup_list";
    const XML_ONREGISTER_LIST_VALUE = "magesail_lists/lists/signup_list";
    const XML_NEWSLETTER_LIST_ENABLED = "magesail_lists/lists/enable_newsletter";
    const XML_NEWSLETTER_LIST_VALUE = "magesail_lists/lists/newsletter_list";

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


    public function __construct(Api $sailthru, Manager $moduleManager, Subscriber $subscribeCtrl) {
        $this->moduleManager = $moduleManager;
        $this->sailthru = $sailthru;
        $this->subscribeCtrl = $subscribeCtrl;
    }


    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Sailthru_MageSail')) {

            $customer = $observer->getData('customer');
            $email = $customer->getEmail();

            try {
                $this->sailthru->client->_eventType = 'CustomerRegister';
                $data = [
                        'id'     => $email,
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

                if ($this->sailthru->getSettingsVal(self::XML_ONREGISTER_LIST_ENABLED)){
                    $list = $this->sailthru->getSettingsVal(self::XML_ONREGISTER_LIST_VALUE);
                    $data["lists"] = [ $list => 1 ];
                }
                // if ($this->sailthru->getSettingsVal(self::XML_NEWSLETTER_LIST_ENABLED) and $this->subscribeCtrl->loadByEmail($email)->isSubscribed()){
                //     $newsletter_list = $this->sailthru->getSettingsVal(XML_NEWSLETTER_LIST_VALUE);
                //     $data["lists"][$newsletter_list] = 1;
                // }



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
