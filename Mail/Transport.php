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
use Sailthru\MageSail\Helper\CustomVariables;
use Sailthru\MageSail\MageClient;

class Transport extends \Magento\Framework\Mail\Transport implements \Magento\Framework\Mail\TransportInterface
{
    
    /** List of `customer` templates which needs additional variables. */
    const CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'customer_create_account_email_template',
        'customer_create_account_email_confirmation_template',
        'customer_create_account_email_confirmed_template',
    ];

    /** List of `order` templates which needs additional variables. */
    const ORDER_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'sales_email_order_template',
    ];

    /** List of `shipping` templates which needs additional variables. */
    const SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS = [
        'sales_email_shipment_template',
    ];

    /** Magento `Generic` template name. */
    const MAGENTO_GENERIC_TEMPLATE = "Magento Generic";

    /** List of templates which needs `isGuest` variable. */
    const TEMPLATES_WITH_IS_GUEST_VAR = [
        'sales_email_order_template',
        'sales_email_order_comment_template',
        'sales_email_order_comment_guest_template',
        'sales_email_shipment_template',
        'sales_email_shipment_comment_template',
        'sales_email_shipment_comment_guest_template',
    ];

    /** @var Magento\Framework\Mail\MessageInterface */
    protected $_message;

    /** @var ClientManager */
    protected $clientManager;

    /** @var MageClient */
    protected $client;

    /** @var Settings */
    protected $sailthruSettings;

    /** @var CustomVariables */
    protected $customVariables;

    /** @var Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var Magento\Sales\Model\Order */
    protected $order;

    /** @var Magento\Sales\Model\Shipment */
    protected $shipment;

    /**
     * Transport constructor.
     * 
     * @param ClientManager                              $clientManager
     * @param Settings                                   $sailthruSettings
     * @param MessageInterface                           $message
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Customer           $customerModel
     * @param \Magento\Sales\Model\Order                 $order
     * @param \Magento\Sales\Model\Order\Shipment        $shipment
     * @param mixed                                      $parameters
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        MessageInterface $message,
        CustomVariables $customVariables,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        $this->storeManager = $storeManager;
        $this->customerModel = $customerModel;
        $this->order = $order;
        $this->shipment = $shipment;
        $this->customVariables = $customVariables;
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
            $vars += $this->needsAdditionalVars($templateData['identifier'], $templateData['variables']);
            # Template name
            $template = $this->sailthruSettings->getTemplateEnabled($templateData['identifier'])
                ? $this->sailthruSettings->getTemplateValue($templateData['identifier'])
                : self::MAGENTO_GENERIC_TEMPLATE;

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


    /**
     * To check if template needs additional variables.
     * 
     * @param  string  $id
     * @param  array   $currentVars
     * 
     * @return array
     */
    public function needsAdditionalVars($id, $currentVars = [])
    {
        switch (true) {
            case in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS):
                $customer = $this->customerModel;
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->loadByEmail($currentVars['customer_email']);
                $data = [
                    'object' => $customer,
                    'type' => 'customer',
                ];
                break;

            case in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS):
                $order = $this->order->loadByIncrementId($currentVars['increment_id']);
                $data = [
                    'object' => $order,
                    'type' => 'order',
                ];
                break;

            case in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS):
                $shipment = $this->shipment->loadByIncrementId($currentVars['shipment_id']);
                $data = [
                    'object' => $shipment,
                    'paymentHtml' => isset($currentVars['payment_html']) ? $currentVars['payment_html'] : '',
                    'comment' => isset($currentVars['shipment_comment']) ? $currentVars['shipment_comment'] : '',
                    'type' => 'shipment',
                ];
                break;

            case in_array($id, self::TEMPLATES_WITH_IS_GUEST_VAR):
                if (strstr($id, 'order')) {
                    $object = $this->order->loadByIncrementId($currentVars['increment_id']);
                    $objectType = 'order';
                } else {
                    $object = $this->shipment->loadByIncrementId($currentVars['shipment_id']);
                    $objectType = 'shipment';
                }
                $data = [
                    'object' => $object,
                    'type' => 'isGuest',
                    'objectType' => $objectType,
                ];
                break;

            default:
                return $currentVars;
        }

        $currentVars += $this->customVariables->getVariables($data);

        return $currentVars;
    }
}
