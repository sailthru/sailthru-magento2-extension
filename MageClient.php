<?php

namespace Sailthru\MageSail;

use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Helper\ScopeResolver;

class MageClient extends \Sailthru_Client
{

    public $_eventType = null;

    /** @var Logger  */
    private $logger;

    /** @var StoreManager  */
    private $storeManager;

    /** @var ModuleListInterface */
    private $moduleList;

    /** @var ScopeResolver  */
    private $scopeResolver;

    public function __construct(
        $api_key, $secret,
        Logger $logger,
        StoreManager $storeManager,
        ModuleListInterface $moduleList,
        ScopeResolver $scopeResolver
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->scopeResolver = $scopeResolver;
        $this->moduleList = $moduleList;
        $options = [ "timeout" => 3000, "connection_timeout" => 3000];
        parent::__construct($api_key, $secret, false, $options);
    }


    protected function httpRequest($action, $data, $method = 'POST', $options = [])
    {
        $logAction = "{$method} /{$action}";
        $storeId = $this->scopeResolver->resolveRequestedStoreId();
        $this->logger->info([
            'action'            => $logAction,
            'event_type'        => $this->_eventType,
            'store_id'          => $storeId,
            'http_request_type' => $this->http_request_type,
            'body'              => $data['json']
        ]);
        try {
            $response = parent::httpRequest($action, $data, $method, $options);
            $this->logger->info([
              'action_response'  => $logAction,
              'event_type '      => $this->_eventType,
              'store_id'         => $storeId,
              'response'         => $response
            ]);
            return $response;
        } catch (\Sailthru_Client_Exception $e) {
            $this->logger->err([
                'error'    => $logAction,
                'store_id' => $storeId,
                'code'     => $e->getCode(),
                'message'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function prepareJsonPayload(array $data, array $binary_data = [])
    {
        $versionString = $this->moduleList->getOne('Sailthru_MageSail')['setup_version'];
        $data['integration'] = "Magento 2 - $versionString";
        return parent::prepareJsonPayload($data, $binary_data);
    }

    public function getSettings()
    {
        return $this->apiGet('settings');
    }

    public function getVerifiedSenders()
    {
        $settings = $this->getSettings();

        return $settings['from_emails'] ?? [];
    }

    /**
     * Log messages. Moved to its own class.
     * @param $message
     * @deprecated
     */
    public function logger($message)
    {
        $this->logger->info($message);
    }
}
