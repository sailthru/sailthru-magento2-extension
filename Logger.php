<?php

namespace Sailthru\MageSail;

use Magento\Store\Model\StoreManager;
use Zend\Log\Logger as ZendLogger;
use Zend\Log\Writer\Stream;

class Logger extends ZendLogger
{

    const SAILTHRU_PATH = '/var/log/sailthru.log';

    /** @var StoreManager  */
    private $storeManager;

    public function __construct(StoreManager $storeManager)
    {
        parent::__construct();
        $streamWriter = new Stream(BP . self::SAILTHRU_PATH);
        $this->addWriter($streamWriter);
        $this->storeManager = $storeManager;
    }

    public function logApiRequest($eventType, $httpType, $method, $action, $payload)
    {
        $store = $this->storeManager->getStore();
        $this->info([
            'action'            => "{$method} /{$action}",
            'event_type'        => $eventType,
            'store_id'          => "{$store->getId()} | {$store->getName()}",
            'http_request_type' => $httpType, // http request type (with or without curl)
            'request'           => $payload
        ]);
    }


}
