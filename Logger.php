<?php

namespace Sailthru\MageSail;

use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;

class Logger
{

    const SAILTHRU_PATH = '/var/log/sailthru.log';

    /** @var StoreManager  */
    private $storeManager;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(StoreManager $storeManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function logApiRequest($eventType, $httpType, $method, $action, $payload)
    {
        $store = $this->storeManager->getStore();
        $this->logger->info([
            'action'            => "{$method} /{$action}",
            'event_type'        => $eventType,
            'store_id'          => "{$store->getId()} | {$store->getName()}",
            'http_request_type' => $httpType, // http request type (with or without curl)
            'request'           => $payload
        ]);
    }
}

