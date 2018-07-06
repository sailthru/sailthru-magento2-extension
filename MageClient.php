<?php

namespace Sailthru\MageSail;

use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Helper\ScopeResolver;

class MageClient extends \Sailthru_Client
{

    public $_eventType = null;

    /** @var string */
    private $version;

    /** @var Logger  */
    private $logger;

    /** @var ScopeResolver */
    private $scopeResolver;

    protected static $TIMEOUT_OPTIONS = [
        "timeout" => 3000,
        "connection_timeout" => 3000
    ];

    protected static $TRUNCATE_RESPONSES = [
        ["GET", "template"],
        ["GET", "settings"]
    ];

    public function __construct($api_key, $secret, $version, Logger $logger, ScopeResolver $scopeResolver)
    {
        parent::__construct($api_key, $secret, false, self::$TIMEOUT_OPTIONS);
        $this->logger = $logger;
        $this->scopeResolver = $scopeResolver;
        $this->version = $version;
    }


    protected function httpRequest($action, $data, $method = 'POST', $options = [])
    {
        $logAction = "{$method} /{$action}";
        $scope= $this->scopeResolver->getScope();
        $scopeString = "{$scope[0]} {$scope[1]}";
        $this->logger->info([
            'action'            => $logAction,
            'event_type'        => $this->_eventType,
            'scope'             => $scopeString,
            'http_request_type' => $this->http_request_type,
            'body'              => $data['json']
        ]);
        try {
            $response = parent::httpRequest($action, $data, $method, $options);
            $this->logger->info([
              'action_response'  => $logAction,
              'event_type '      => $this->_eventType,
              'scope'            => $scopeString,
              'response'         => !$this->truncateResponse($method, $action) ? $response : "<TRUNCATED>"
            ]);
            return $response;
        } catch (\Sailthru_Client_Exception $e) {
            $this->logger->err([
                'error'    => $logAction,
                'scope'    => $scopeString,
                'code'     => $e->getCode(),
                'message'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function prepareJsonPayload(array $data, array $binary_data = [])
    {
        $data['integration'] = "Magento 2 - $this->version";
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

    private function truncateResponse($method, $action)
    {
        return in_array([$method, $action], self::$TRUNCATE_RESPONSES);
    }
}
