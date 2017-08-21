<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Sailthru_Client_Exception;

abstract class AbstractSource implements ArrayInterface
{

    protected $clientManager;

    /**
     * Catchable function converting Sailthru API -> Source Model Display
     * @return array
     * @throws \Sailthru_Client_Exception
     */
    abstract protected function getDisplayData();

    private static $error_responses = [
        2 => "Please check your API credentials",
        3 => "Please check your API credentials",
        5 => "Please check your API credentials",
        4 => "Disallowed IP. Please reach out to Sailthru support"
    ];

    public function __construct(\Sailthru\MageSail\Helper\ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    public function toOptionArray()
    {
        try {
            return $this->getDisplayData();
        } catch (Sailthru_Client_Exception $e) {
            $error_message = array_key_exists($e->getCode(), self::$error_responses)
                ? self::$error_responses[$e->getCode()]
                : "({$e->getCode()}) {$e->getMessage()}";
            return [
                ['value'=>0, 'label'=>__("error: {$error_message}")]
            ];
        }
    }
}
