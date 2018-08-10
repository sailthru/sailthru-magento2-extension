<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\Templates as SailthruTemplates;
use Sailthru\MageSail\MageClient;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{

    /** @var Message */
    protected $_message;

    /** @var ClientManager */
    protected $clientManager;

    /** @var Settings */
    protected $sailthruSettings;

    /** @var SailthruTemplates */
    protected $sailthruTemplates;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var RequestInterface  */
    protected $request;

    /**
     * Transport constructor.
     *
     * @param ClientManager $clientManager
     * @param Settings $sailthruSettings
     * @param MessageInterface $message
     * @param SailthruTemplates $sailthruTemplates
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param null|array
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        MessageInterface $message,
        SailthruTemplates $sailthruTemplates,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruTemplates = $sailthruTemplates;
        $this->storeManager = $storeManager;
        $this->request = $request;
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

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            $templateData = $this->_message->getTemplateInfo();

            // Patch for emails sent from unscoped admin sections.
            $storeId = isset($templateData['storeId'])
                ? $templateData['storeId']
                : $this->storeManager->getStore()->getId();
            $this->request->setParams(['store' => $storeId]);

            if ($this->sailthruSettings->getTransactionalsEnabled($storeId)) {
                $this->sendViaAPI($templateData, $storeId);
            } else {
                parent::sendMessage();
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
        }
    }

    /**
     * To send `Magento Generic` or `Magento Specific` template.
     *
     * @param  array $templateData
     * @throws MailException
     */
    public function sendViaAPI($templateData, $storeId)
    {
        $client = $this->clientManager->getClient(true, $storeId);

        $vars = [
            "subj" => $this->_message->getSubject(),
            "content" => $this->_message->getBody()->getRawContent(),
        ];

        try {
            # Get template name
            $template = $this->sailthruSettings->getTemplateName($templateData['identifier'], $storeId);
            # Vars used in Sailthru Magento 1 extension and template file.
            $vars += $this->sailthruSettings->getTemplateAdditionalVariables(
                $template['orig_template_code'],
                $templateData['variables']
            );

            $templateName = $template['name'];
            if (!$this->sailthruTemplates->templateExists($templateName, $storeId)) {
                $this->sailthruTemplates->saveTemplate($templateName, $this->sailthruSettings->getSender($storeId), $storeId);
            }

            $message = [
                "template" => $templateName,
                "email" => $this->cleanEmails($this->recipients),
                "vars" => $vars,
            ];

            $response = $client->apiPost('send', $message);
            if (isset($response["error"])) {
                $client->logger($response['errormsg']);
                throw new MailException(__($response['errormsg']));
            }
        } catch (\Exception $e) {
            throw new MailException(__("Couldn't send the mail {$e->getMessage()}"));
        }
    }
}
