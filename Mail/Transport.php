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
use Sailthru\MageSail\Helper\Customer;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    
    /**
     * List of `customer` templates which needs additional variables.
     */
    const CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'customer_create_account_email_template',
        'customer_create_account_email_confirmation_template',
        'customer_create_account_email_confirmed_template',
    ];

    /**
     * List of `order` templates which needs additional variables.
     * TODO: add real list of template ids.
     */
    const ORDER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'test1',
        'test2',
    ];

    /**
     * List of `shipping` templates which needs additional variables.
     * TODO: add real list of template ids.
     */
    const SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'test1',
        'test2',
    ];

    /**
     * Magento `Generic` template name.
     */
    const MAGENTO_GENERIC_TEMPLATE = "Magento Generic";

    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;

    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var MageClient
     */
    protected $client;

    /**
     * @var Settings
     */
    protected $sailthruSettings;

    /**
     * @var Customer
     */
    protected $customerHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** 
     * @var \Magento\Customer\Model\Customer
     */
    protected $cutomerModel;

    /** 
     * @var \Magento\Customer\Model\Group
     */
    protected $customerGroupCollection;

    /** 
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;
    
    /**
     * Transport constructor.
     * 
     * @param ClientManager                               $clientManager
     * @param Settings                                    $sailthruSettings
     * @param MessageInterface                            $message
     * @param Customer                                    $customerHelper
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Customer\Model\Customer            $customerModel
     * @param \Magento\Customer\Model\Group               $customerGroupCollection
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param null                                        $parameters
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        MessageInterface $message,
        Customer $customerHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        $this->customerHelper = $customerHelper;
        $this->storeManager = $storeManager;
        $this->customerModel = $customerModel;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->timezone = $timezone;
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
        # To get array with template variables and template identifier
        # use $this->_message->getTemplateInfo();
        $templateData = $this->_message->getTemplateInfo();

        if ($this->sailthruSettings->getTransactionalsEnabled()) {
            if ($this->sailthruSettings->getTemplateEnabled($templateData['identifier'])) {
                # send template specific
                self::sendTemplateSpecific();
            } else {
                # send magento generic
                self::sendMagentoGeneric();
            }
        } else {
            parent::_sendMail();
        }
    }

    /**
     * To send specific template.
     */
    public function sendTemplateSpecific()
    {
        try {
            $templateInfo = $this->_message->getTemplateInfo();
            $vars = [
                "subj"    => $this->_message->getSubject(),
                "content" => $this->_message->getBody()->getRawContent(),
            ];

            $vars += self::needsAdditionalVars($templateInfo['identifier'], $templateInfo['variables']);

            $message = [
                "template" => 'magento_'.$templateInfo['identifier'],
                "email" => $this->cleanEmails($this->recipients),
                "vars" => $vars,
            ];

            $response = $this->client->apiPost('send', $message);
            if (isset($response["error"])) {
                $this->client->logger($response['errormsg']);
                throw new MailException(__($response['errormsg']));
            }
        } catch(\Exception $e) {
            throw new MailException(__("Couldn't send the mail {$e}"));
        }
    }

    /**
     * To send Magento Generic template.
     */
    public function sendMagentoGeneric()
    {
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
     * To check if template needs additional variables.
     * 
     * @param  string  $id             Template identifier.
     * @param  array   $currentVars    Current template variables.
     * 
     * @return array
     */
    public function needsAdditionalVars($id, $currentVars)
    {
        $vars = $currentVars;

        if (in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            # Load customer by email
            $customer = $this->customerModel;
            $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
            $customer->loadByEmail($currentVars['customer_email']);
            
            # Add additional variables.
            $vars += $this->customerHelper->getCustomerVariable(
                $customer,
                $this->storeManager,
                $this->customerGroupCollection,
                $this->timezone
            );
        } else if (in_array($id, self::ORDER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            # Load order
        } else if (in_array($id, self::SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            # Load shipment
        }

        return $vars;
    }
}
