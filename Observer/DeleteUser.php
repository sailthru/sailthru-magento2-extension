<?php

namespace Sailthru\MageSail\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings;

class DeleteUser implements ObserverInterface
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var Settings
     */
    protected $sailthruSettings;

    /**
     * @param ClientManager $clientManager
     * @param Settings $sailthruSettings
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
    }

    /**
     * Remove customer from Sailthru
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $storeId = $customer->getStoreId();
        if (empty($this->sailthruSettings->isRemoveUserInSailthru())) {
            return;
        }
        $client = $this->clientManager->getClient($storeId);
        try {
            $client->_eventType = 'DeleteCustomer';
            $client->apiDelete('user', [
                'id' => $customer->getEmail(),
            ]);
        } catch (\Throwable $t) {
            $client->logger(__('Error of remove customer data from Sailthru'));
            $client->logger($t->getMessage());
        }
    }
}
