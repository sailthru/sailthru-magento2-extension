<?php
/**
 * Mail Transport
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\MageClient;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    
    const MAGENTO_GENERIC_TEMPLATE = "Magento Generic";

    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;

    /**
     * @var ClientManager
     */
    protected $clientManager;

    /** @var  MageClient */
    protected $client;

    /** @var Settings */
    protected $sailthruSettings;

    /**
     * Transport constructor.
     *
     * @param ClientManager    $clientManager
     * @param Settings         $sailthruSettings
     * @param MessageInterface $message
     * @param null             $parameters
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        MessageInterface $message,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        parent::__construct($message, $parameters);
    }

    public function checkAndSetGenericTemplate()
    {
        $response = $this->client->getTemplate(self::MAGENTO_GENERIC_TEMPLATE);
        if (isset($response["error"]) && $response['error'] == 14) {
            $options = [
                "content_html" => "{content} {beacon}",
                "subject" => "{subj}",
                "from_email" => $this->sailthruSettings->getSender(),
                "is_link_tracking" => 1
            ];
            $response = $this->client->saveTemplate(self::MAGENTO_GENERIC_TEMPLATE, $options);
            if (isset($response["error"])) {
                if ($response['error'] == 14) {
                    $this->checkAndSetGenericTemplate();
                }
                if ($response['error'] != 14) {
                    $this->client->logger($response['errormsg']);
                    throw new MailException(__($response['errormsg']));
                }
            }
        }
    }

    public function _sendMail()
    {
        if ($this->sailthruSettings->getTransactionalsEnabled()) {
            try {
                $this->checkAndSetGenericTemplate();
                $message = [
                    "template" => self::MAGENTO_GENERIC_TEMPLATE,
                    "email"  => $this->cleanEmails($this->recipients),
                    "vars"     => [
                        "subj"    => $this->_message->getSubject(),
                        "content" => $this->_message->getBody()->getRawContent(),
                    ],
                ];
                $response = $this->client->apiPost('send', $message);
                if (isset($response["error"])) {
                    $this->client->logger($response['errormsg']);
                    throw new MailException(__($response['errormsg']));
                }
            } catch (\Exception $e) {
                throw new MailException(__("Couldn't send the mail {$e}"));
            }
        } else {
            parent::_sendMail();
        }
    }

    public function cleanEmail($str)
    {
        $startPart = strpos($str, '<');
        if ($startPart === false) {
            return $str;
        }
        $email = substr($str, $startPart + 1);
        $email = substr($email, 0, -1);
        return $email;
    }

    public function cleanEmails($emailStr)
    {
        return implode(",", array_map([ $this, 'cleanEmail' ], explode(",", $emailStr)));
    }
}
