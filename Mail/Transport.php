<?php
/**
 * Mail Transport
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

use Sailthru\MageSail\Helper\Api;
use Magento\Framework\Exception\MailException;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    
    const MAGENTO_GENERIC_TEMPLATE = "Magento Generic";

    const SILENT_ERRORS = [
        32,
        33,
        34,
        35,
        37
    ];
    
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;

    /**
     * @var \Sailthru\MageSail\Helper\Api
     */
    protected $sailthru;

    /**
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(
        Api $sailthru,
        \Magento\Framework\Mail\MessageInterface $message,
        $parameters = null
    ) {
        $this->sailthru = $sailthru;
        parent::__construct($message, $parameters);
    }

    public function checkAndSetGenericTemplate()
    {
        $response = $this->sailthru->client->getTemplate(self::MAGENTO_GENERIC_TEMPLATE);
        if (isset($response["error"]) && $response['error'] == 14) {
            $options = [
                "content_html" => "{content} {beacon}",
                "subject" => "{subj}",
                "from_email" => $this->sailthru->getSender(),
                "is_link_tracking" => 1
            ];
            $response = $this->sailthru->client->saveTemplate(self::MAGENTO_GENERIC_TEMPLATE, $options);
            if (isset($response["error"])) {
                if ($response['error'] == 14) {
                    $this->checkAndSetGenericTemplate();
                }
                if ($response['error'] != 14) {
                    $this->sailthru->logger($response['errormsg']);
                    throw new MailException(__($response['errormsg']));
                }
            }
        }
    }

    public function _sendMail()
    {
        if ($this->sailthru->getTransactionalsEnabled()) {
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
                $response = $this->sailthru->client->apiPost('send', $message);
                if (isset($response["error"])) {
                    $this->sailthru->logger($response['errormsg']);
                    if (!in_array($response["error"], self::SILENT_ERRORS)) {
                        throw new MailException(__($response['errormsg']));
                    }
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
