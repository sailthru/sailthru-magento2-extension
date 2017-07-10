<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Customer\Model\Session;
use Magento\CUstomer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\Customer as SailthruCustomer;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;

class CustomerAccountEdit implements ObserverInterface
{

    private $moduleManager;
    private $customerModel;
    private $storeManager;
    private $sailthruClient;
    private $sailthruCookie;
    private $sailthruCustomer;
    private $sailthruSettings;

    const SETTING_ENABLED = "magesail_lists/lists/enable_signup_list";

    const SETTING_VALUE = "magesail_lists/lists/signup_list";

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        SailthruCustomer $sailthruCustomer,
        Manager $moduleManager,
        Customer $customerModel,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->sailthruClient = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruCustomer = $sailthruCustomer;
    }

    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Sailthru_MageSail')) {
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
            $this->customerModel->setWebsiteId($websiteId);
            $email = $observer->getData('email');
            $customer = $this->customerModel->loadByEmail($email);
            $sid = $customer->getData('sailthru_id');

            try {
                $this->sailthruClient->_eventType = 'CustomerUpdate';

                $data = [
                        'id' => $sid ? $sid : $email,
                        'fields' => ['keys' => 1],
                        'keysconflict' => 'merge',
                        'keys'=> [
                            'email' => $email
                        ],
                        'vars' => [
                            'firstName' => $customer->getFirstname(),
                            'lastName'  => $customer->getLastname(),
                            'name'  => "{$customer->getFirstname()} {$customer->getLastname()}"
                        ]
                ];

                if ($address = $this->sailthruSettings->getAddressVarsByCustomer($customer)) {
                    $data['vars'] += $address;
                }

                if ($customer->getCustomAttribute('is_subscribed')) {
                    $data["lists"] = ["Newsletter"=>1];
                }

                $response = $this->sailthruClient->apiPost('user', $data);
                $this->sailthruCookie->set($response["keys"]["cookie"]);
            } catch (\Sailthru_Client_Exception $e) {
                $this->sailthruClient->logger($e);
            } catch (\Exception $e) {
                $this->sailthruClient->logger($e);
            }
        }
    }
}
