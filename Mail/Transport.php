<?php
/**
 * Mail Transport
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

use Sailthru\MageSail\Helper\Api;

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
    public function __construct(Api $sailthru, \Magento\Framework\Mail\MessageInterface $message, $parameters = null)
    {
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
                if ($response['error'] == 14) $this->checkAndSetGenericTemplate();
                if ($response['error'] != 14) throw new \Exception($response['errormsg']);
            }
        }
    }


    public function _sendMail()
    {
        if ($this->sailthru->getTransactionalsEnabled())
        {
            try {
                $this->sailthru->logger("trying to send email!");
                $this->checkAndSetGenericTemplate(); 
                $message = [
                    "template" => self::MAGENTO_GENERIC_TEMPLATE,
                    "email"  => $this->cleanEmails($this->recipients),
                    "vars"     => [
                        "subj"    => $this->_message->getSubject(),
                        "content" => $this->_message->getBody()->getRawContent(),
                    ],
                ];
                $this->sailthru->logger($message);
                $response = $this->sailthru->client->apiPost('send', $message);
                if (isset($response["error"])) {
                    throw new \Exception($response["errormsg"]);
                }
            } catch (\Exception $e) {
                $this->sailthru->logger($e);
                parent::_sendMail();
                throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
            }
        }
        else {
            parent::_sendMail();
        }

    }


    static function cleanEmail($str){
        $startPart = strpos($str, '<') + 1;
        $email = substr($str, $startPart);
        $email = substr($email, 0, -1);
        return $email;        
    }


    public function cleanEmails($emailStr){
        return implode(",", array_map( array( $this, 'cleanEmail'), explode(",", $emailStr)));
    }


}
