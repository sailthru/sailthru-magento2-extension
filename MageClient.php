<?php

namespace Sailthru\MageSail;

class MageClient extends \Sailthru_Client
{

    public $_eventType = null;

    private $httpHeaders = ["User-Agent: Sailthru API PHP5 Client"];

    private $logFileURI = null;

    /**
     * Instantiate a new client; constructor optionally takes overrides for api_uri and whether
     * to share the version of PHP that is being used.
     *
     * @param string $api_key
     * @param string $secret
     * @param string $api_uri
     * @param array $options - optional parameters for connect/read timeout
     * @param boolean $show_version
     */
    public function __construct($api_key, $secret, $logURI)
    {
        if ($logURI) {
            $this->logFileURI = $logURI;
        }
        $options = [ "timeout" => 3000, "connection_timeout" => 3000];
        parent::__construct($api_key, $secret, false, $options);
    }

    /**
     * Perform an HTTP request, checking for curl extension support
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    protected function httpRequest($action, $data, $method = 'POST', $options = [])
    {
        $this->logger(
            [
            'action'            => $action,
            'request'           => $data['json'],
            'http_request_type' => $this->http_request_type,
            'event_type'        => $this->_eventType,
            ],
            "{$method} REQUEST"
        );
        $json = parent::httpRequest($action, $data, $method, $options);
        $this->logger($json);
        return $json;
    }

    protected function prepareJsonPayload(array $data, array $binary_data = [])
    {
        $data['integration'] = "Magento 2";
        return parent::prepareJsonPayload($data, $binary_data);
    }

    public function getSettings()
    {
        return $this->apiGet('settings');
    }

    public function getVerifiedSenders()
    {
        $settings = $this->getSettings();
        return $settings["from_emails"];
    }

    public function logger($message)
    {
        try {
            $writer = new \Zend\Log\Writer\Stream(BP . $this->logFileURI);
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($message);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
