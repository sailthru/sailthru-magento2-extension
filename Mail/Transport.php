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
use Sailthru\MageSail\Helper\Customer;
use Sailthru\MageSail\Helper\Order;
use Sailthru\MageSail\Helper\Shipment;
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

    /** @var \Magento\Framework\Mail\MessageInterface */
    protected $_message;

    /** @var ClientManager */
    protected $clientManager;

    /** @var MageClient */
    protected $client;

    /** @var Settings */
    protected $sailthruSettings;

    /** @var Customer */
    protected $customerHelper;

    /** @var Order */
    protected $orderHelper;

    /** @var Shipment */
    protected $shipmentHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Sales\Model\Order */
    protected $order;

    /** @var \Magento\Sales\Model\Shipment */
    protected $shipment;

    /**
     * Transport constructor.
     * 
     * @param ClientManager                              $clientManager
     * @param Settings                                   $sailthruSettings
     * @param MessageInterface                           $message
     * @param Customer                                   $customerHelper
     * @param Order                                      $orderHelper
     * @param Shipment                                   $shipmentHelper
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
        Customer $customerHelper,
        Order $orderHelper,
        Shipment $shipmentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
        $this->customerHelper = $customerHelper;
        $this->orderHelper = $orderHelper;
        $this->shipmentHelper = $shipmentHelper;
        $this->storeManager = $storeManager;
        $this->customerModel = $customerModel;
        $this->order = $order;
        $this->shipment = $shipment;
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
            self::sendViaAPI($templateData);
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
            $vars += self::needsAdditionalVars($templateData['identifier'], $templateData['variables']);

            if ($this->sailthruSettings->getTemplateEnabled($templateData['identifier'])) {
                $template = 'magento_'.$templateData['identifier'];
            } else {
                $template = self::MAGENTO_GENERIC_TEMPLATE;
                $this->checkAndSetGenericTemplate();
            }

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
        # Looks for additional vars for `customer` templates.
        if (in_array($id, self::CUSTOMER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            if (isset($currentVars['customer_email'])) {
                $customer = $this->customerModel;
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->loadByEmail($currentVars['customer_email']);
                $currentVars += $this->customerHelper->getCustomerVars($customer);
            } else {
                throw new MailException(__("Couldn't find customer."));
            }   
        }

        # Looks for additional vars for `order` templates.
        if (in_array($id, self::ORDER_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            if (isset($currentVars['increment_id'])) {
                $order = $this->order->loadByIncrementId($currentVars['increment_id']);
                $currentVars += $this->orderHelper->getOrderVars($order);
            } else {
                throw new MailException(__("Couldn't find order."));
            }
        }

        # Looks for additional vars for `shipment` templates.
        if (in_array($id, self::SHIPMENT_TEMPLATES_FOR_ADDITIONAL_VARS)) {
            if (isset($currentVars['shipment_id'])) {
                $shipment = $this->shipment->loadByIncrementId($currentVars['shipment_id']);
                $currentVars += $this->shipmentHelper->getShipmentVars(
                    $shipment,
                    isset($currentVars['payment_html']) ? $currentVars['payment_html'] : '',
                    isset($currentVars['shipment_comment']) ? $currentVars['shipment_comment'] : ''
                );
            } else {
                throw new MailException(__("Couldn't find shipment."));
            }
        }

        return $currentVars;
    }
}
