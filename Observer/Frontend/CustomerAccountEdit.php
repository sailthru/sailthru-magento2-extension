<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\CUstomer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Helper\Customer as SailthruCustomer;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\VarHelper;

class CustomerAccountEdit implements ObserverInterface
{

    private $moduleManager;
    private $customerModel;
    private $storeManager;
    private $sailthruClient;
    private $sailthruCookie;
    private $sailthruCustomer;
    private $sailthruSettings;
    private $sailthruVars;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        SailthruCustomer $sailthruCustomer,
        Manager $moduleManager,
        Customer $customerModel,
        StoreManagerInterface $storeManager,
        VarHelper $sailthruVars
    ) {
        $this->moduleManager = $moduleManager;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->sailthruClient = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruCustomer = $sailthruCustomer;
        $this->sailthruVars = $sailthruVars;
    }

    public function execute(Observer $observer) {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $this->customerModel->setWebsiteId($websiteId);
        $email = $observer->getData('email');
        $customer = $this->customerModel->loadByEmail($email);
        $storeId = $customer->getStore()->getId();
        $this->sailthruClient = $this->sailthruClient->getClient(true, $storeId);
        $sid = $customer->getData('sailthru_id');
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);

        try {
            $this->sailthruClient->_eventType = 'CustomerUpdate';

            $data = [
                'id'           => $sid ? $sid : $email,
                'fields'       => [ 'keys' => 1 ],
                'keysconflict' => 'merge',
                'keys'         => [
                    'email' => $email
                ],
                'vars'         => [
                    $varKeys['firstname'] => $customer->getFirstname(),
                    $varKeys['lastname']  => $customer->getLastname(),
                    'name'      => "{$customer->getFirstname()} {$customer->getLastname()}"
                ]
            ];

            if ($address = $this->sailthruSettings->getAddressVarsByCustomer($customer)) {
                $data['vars'] += $address;
            }

            if ($this->sailthruSettings->newsletterListEnabled($storeId) &&
                $customer->getCustomAttribute('is_subscribed')
            ) {
                $data["lists"] = [ "Newsletter" => 1 ];
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
