<?php
/**
 * Mail Transport
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

use Sailthru\MageSail\Helper\Api;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    
    const MAGENTO_GENERIC_TEMPLATE = "Magento Generic";

    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;

    /**
     * @param MessageInterface $message
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
            ];
            $response = $this->sailthru->client->saveTemplate(self::MAGENTO_GENERIC_TEMPLATE, $options);
            if (isset($response["error"])) {
                if ($response['error'] == 14) {
                    $this->checkAndSetGenericTemplate();
                }
                if ($response['error'] != 14) {
                    throw new LocalizedException($response['errormsg']);
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
                    throw new LocalizedException($response["errormsg"]);
                }
            } catch (\Exception $e) {
                $this->sailthru->logger($e->getMessage());
                throw new \Magento\Framework\Exception\MailException(__("Couldn't send the mail"));
            }
        } else {
            parent::_sendMail();
        }
    }

    public static function cleanEmail($str)
    {
        $startPart = strpos($str, '<') + 1;
        $email = substr($str, $startPart);
        $email = substr($email, 0, -1);
        return $email;
    }

    public static function cleanEmails($emailStr)
    {
        return implode(",", array_map([ $this, 'cleanEmail' ], explode(",", $emailStr)));
    }
}
