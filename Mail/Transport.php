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
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\MageClient;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    /** @var Magento\Framework\Mail\MessageInterface */
    protected $_message;

    /** @var ClientManager */
    protected $clientManager;

    /** @var MageClient */
    protected $client;

    /** @var Settings */
    protected $sailthruSettings;

    /** @var Api */
    protected $apiHelper;
    
    /**
     * Transport constructor.
     * 
     * @param ClientManager    $clientManager
     * @param Settings         $sailthruSettings
     * @param MessageInterface $message
     * @param Api              $apiHelper
     * @param mixed
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        MessageInterface $message,
        Api $apiHelper,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        $this->apiHelper = $apiHelper;
        parent::__construct($message, $parameters);
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

    public function _sendMail()
    {
        # To get array with template variables and template identifier
        # use $this->_message->getTemplateInfo();
        $templateData = $this->_message->getTemplateInfo();
        if ($this->sailthruSettings->getTransactionalsEnabled()) {
            $this->sendViaAPI($templateData);
        } else {
            parent::_sendMail();
        }
    }

    /**
     * To send `Magento Generic` or `Magento Specific` template.
     * 
     * @param  array $templateData
     */
    public function sendViaAPI($templateData)
    {
        try {
            $vars = [
                "subj" => $this->_message->getSubject(),
                "content" => $this->_message->getBody()->getRawContent(),
            ];

            # Vars used in Sailthru Magento 1 extension and template file.
            $vars += $this->sailthruSettings->getTemplateAdditionalVariables(
                $templateData['identifier'],
                $templateData['variables']
            );
            # Get template name
            $template = $this->sailthruSettings->getTemplateName($templateData['identifier']);
            # Create\Update template
            $this->apiHelper->saveTemplate($template, $this->sailthruSettings->getSender());

            $message = [
                "template" => $template,
                "email" => $this->cleanEmails($this->recipients),
                "vars" => $vars,
            ];

            $response = $this->client->apiPost('send', $message);
            if (isset($response["error"])) {
                $this->client->logger($response['errormsg']);
                throw new MailException(__($response['errormsg']));
            }
        } catch (\Exception $e) {
            throw new MailException(__("Couldn't send the mail {$e}"));
        }
    }
}
