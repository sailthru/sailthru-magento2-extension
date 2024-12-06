<?php

namespace Sailthru\MageSail\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mview\View\SubscriptionFactory;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings as SailthruSettings;
use Sailthru\MageSail\Cookie\Hid as SailthruCookie;
use Sailthru\MageSail\Helper\VarHelper;
use Sailthru\MageSail\Logger;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\CustomerFactory;
use Magento\CUstomer\Model\Customer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\Subscriber;

class CustomerRegistered implements ObserverInterface
{
    private $clientManager;
    private $sailthruSettings;
    private $sailthruCookie;
    private $sailthruVars;
    private $logger;
    private $customerRegistry;
    protected $customerFactory;
    private $customerModel;
    private $storeManager;
    private $subscriber;

    public function __construct(
        ClientManager $clientManager,
        SailthruSettings $sailthruSettings,
        SailthruCookie $sailthruCookie,
        VarHelper $sailthruVars,
        Logger $logger,
        CustomerRegistry $customerRegistry,
        CustomerFactory $customerFactory,
        Customer $customerModel,
        StoreManagerInterface $storeManager,
        Subscriber $subscriber
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruCookie = $sailthruCookie;
        $this->sailthruVars = $sailthruVars;
        $this->logger = $logger;
        $this->customerRegistry = $customerRegistry;
        $this->customerFactory = $customerFactory;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->subscriber = $subscriber;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        try {
            // Get is_subscribed value by using Customer Model
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $this->customerModel->setWebsiteId($websiteId);
            $customerDataByEvent = $observer->getEvent()->getCustomer();
            $customerEmail = $observer->getData('email');
            $customerModel = $this->customerModel->loadByEmail($customerEmail);
            $loadByCustomerModel = $customerModel->getCustomAttribute('is_subscribed');

            // Get is_subscribed by Subscriber class (Getting is_subscribed value using this way on local)
            $subscriberDetails = $this->subscriber->loadByEmail($customer->getEmail());
            $status = $subscriberDetails->getStatus();
            $isSubscribedNewValue = ($status == Subscriber::STATUS_SUBSCRIBED ? 1 : 0);
            $this->logger->info("#############SubscribedNewValue [$isSubscribedNewValue]###############");

            // Get is_subscribed value by using customerFactory
            $loadCustomerByCustomerFactory = $this->customerFactory->create()->load($customerDataByEvent->getId());
            $isSubscribedValueFromCustomerFactory = $loadCustomerByCustomerFactory->getIsSubscribed();
            $isSubscribedValueFromDataModel = $loadCustomerByCustomerFactory->getDataModel()->getCustomAttribute('is_subscribed');

            // Get is_subscribed value by using customerRegistry
            $loadCustomerByCustomerRegistry = $this->customerRegistry->retrieve($customerDataByEvent->getId());
            $isSubscribedValueFromCustomerRegistry = $loadCustomerByCustomerRegistry->getIsSubscribed();

            //  Get is_subscribed value by using Event AccountController  (Getting is_subscribed value using this way on local)
            $isSubscribed = $observer->getEvent()->getAccountController()->getRequest()->getParam('is_subscribed');

            if ($isSubscribed != '') { // Troubleshooting: If $isSubscribed is empty then get value from $isSubscribedNewValue
                $optOutEmail = $isSubscribed == '1' ? "none" : "basic";
            } else {
                $optOutEmail = $isSubscribedNewValue == '1' ? "none" : "basic";
            }
            $this->logger->info("CustomerRegistration IsSubscriptionValues loadCustomerByCustomerFactory: [$isSubscribedValueFromCustomerFactory] loadCustomerByCustomerRegistry: [$isSubscribedValueFromCustomerRegistry] & isSubscribedValueFromDataModel: [$isSubscribedValueFromDataModel]");
            $this->logger->info("CustomerRegistration IsSubscriptionValues loadByCustomerModel: [$loadByCustomerModel] & AccountController: [$isSubscribed] & [$optOutEmail] & isSubscribedNewValue [$isSubscribedNewValue]");
        } catch (\Exception $e) {
            $this->logger->error("Error in CustomerRegistration # {$e->getMessage()}");
            $optOutEmail = "basic";
        }
        $storeId = $customer->getStoreId();
        $client = $this->clientManager->getClient($storeId);
        $email = $customer->getEmail();
        $selectedCase = $this->sailthruSettings->getSelectCase($storeId);
        $varKeys = $this->sailthruVars->getVarKeys($selectedCase);
        $data = [
            'id'     => $email,
            'optout_email' => $optOutEmail,
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
            $client->_eventType = 'CustomerRegister';
            $response = $client->apiPost('user', $data);
            $this->sailthruCookie->set($response['keys']['cookie']);
        } catch (\Sailthru_Client_Exception $e) {
            $client->logger($e);
        }
    }
}
