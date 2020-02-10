<?php
namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\Templates as SailthruTemplates;
use Sailthru\MageSail\MageClient;
use Sailthru\MageSail\Mail\Transport\SailthruFactory as SailthruTransportFactory;
use Sailthru\MageSail\Mail\Queue\EmailSendPublisher;
use Zend\Mail\Message as ZendMessage;
use Zend\Mail\Address\AddressInterface;
use Zend\Mail\Header\HeaderInterface;

class Transport extends \Magento\Email\Model\Transport
{
    /** @var ClientManager */
    protected $clientManager;

    /** @var Settings */
    protected $sailthruSettings;

    /** @var SailthruTemplates */
    protected $sailthruTemplates;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var SailthruTransportFactory */
    protected $sailthruTransportFactory;

    /** @var EmailSendPublisher */
    protected $emailSendPublisher;

    /** @var RequestInterface  */
    protected $request;

    /** @var string */
    protected $operatingSystem;

    /**
     * Transport constructor.
     *
     * @param ClientManager $clientManager
     * @param Settings $sailthruSettings
     * @param EmailMessageInterface $message
     * @param ScopeConfigInterface $scopeConfig
     * @param SailthruTemplates $sailthruTemplates
     * @param StoreManagerInterface $storeManager
     * @param SailthruTransportFactory $sailthruTransportFactory
     * @param EmailSendPublisher $emailSendPublisher
     * @param RequestInterface $request
     * @param null|array
     */
    public function __construct(
        ClientManager $clientManager,
        Settings $sailthruSettings,
        EmailMessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        SailthruTemplates $sailthruTemplates,
        StoreManagerInterface $storeManager,
        SailthruTransportFactory $sailthruTransportFactory,
        EmailSendPublisher $emailSendPublisher,
        RequestInterface $request,
        $parameters = null
    ) {
        $this->clientManager = $clientManager;
        $this->sailthruSettings = $sailthruSettings;
        $this->sailthruTemplates = $sailthruTemplates;
        $this->storeManager = $storeManager;
        $this->sailthruTransportFactory = $sailthruTransportFactory;
        $this->emailSendPublisher = $emailSendPublisher;
        $this->request = $request;
        parent::__construct($message, $scopeConfig, $parameters);
    }

    /**
     * Send a mail using this transport
     *
     * @return Transport
     *
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            $templateData = $this->getMessage()->getTemplateInfo();

            // Patch for emails sent from unscoped admin sections.
            $storeId = isset($templateData['storeId'])
                ? $templateData['storeId']
                : $this->storeManager->getStore()->getId();
            $this->request->setParams(['store' => $storeId]);
            $template = $this->sailthruSettings->getTemplateName($templateData['identifier'], $storeId);

            if ($this->sailthruSettings->getTransactionalsEnabled($storeId) && $template['name'] != 'disableSailthru') {
                $emailData = $this->getEmailData();
                if ($this->sailthruSettings->getTransactionalsProcessQueueEnabled($storeId)) {
                    $this->emailSendPublisher->execute([
                        'template_data' => $templateData,
                        'email_data'    => $emailData,
                        'store_id'      => $storeId,
                    ]);

                    return $this;
                }
                /** @var Transport\Sailthru $transport */
                $transport = $this->sailthruTransportFactory->create([
                    'data' => [
                        'template_data' => $templateData,
                        'email_data'    => $emailData,
                        'store_id'      => $storeId,
                    ],
                ]);
                $transport->sendMessage();

                return $this;
            }

            parent::sendMessage();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
        }

        return $this;
    }

    /**
     * Get data for email
     *
     * @return array
     */
    protected function getEmailData()
    {
        $message = ZendMessage::fromString($this->getMessage()->getRawMessage());

        return [
            'to'      => $this->prepareRecipients($message),
            'subject' => $this->prepareSubject($message),
            'content' => $this->getMessage()->getDecodedBodyText()
        ];
    }

    /**
     * Prepare recipients list
     *
     * @param  \Zend\Mail\Message $message
     * @throws RuntimeException
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function prepareRecipients(\Zend\Mail\Message $message)
    {
        $headers = $message->getHeaders();

        $hasTo = $headers->has('to');
        if (!$hasTo && !$headers->has('cc') && !$headers->has('bcc')) {
            throw new RuntimeException(
                'Invalid email; contains no at least one of "To", "Cc", and "Bcc" header'
            );
        }

        if (! $hasTo) {
            return '';
        }

        /** @var Mail\Header\To $to */
        $to   = $headers->get('to');
        $list = $to->getAddressList();
        if (count($list) == 0) {
            throw new RuntimeException('Invalid "To" header; contains no addresses');
        }

        // If not on Windows, return normal string
        if (!$this->isWindowsOs() && version_compare($this->sailthruSettings->getMagentoVersion(), '2.3.3', '<')) {
            return $to->getFieldValue(HeaderInterface::FORMAT_ENCODED);
        }

        // Otherwise, return list of emails
        $addresses = [];
        foreach ($list as $address) {
            $addresses[] = $address->getEmail();
        }
        $addresses = implode(', ', $addresses);
        return $addresses;
    }

    /**
     * Prepare the subject line string
     *
     * @param  \Zend\Mail\Message $message
     * @return string
     */
    protected function prepareSubject(\Zend\Mail\Message $message)
    {
        $headers = $message->getHeaders();
        if (! $headers->has('subject')) {
            return;
        }
        $header = $headers->get('subject');
        return $header->getFieldValue(HeaderInterface::FORMAT_ENCODED);
    }

    /**
     * Prepare the body string
     *
     * @param  \Zend\Mail\Message $message
     * @return string
     */
    protected function prepareBody(\Zend\Mail\Message $message)
    {
        if (! $this->isWindowsOs()) {
            // *nix platforms can simply return the body text
            return $message->getBodyText();
        }

        // On windows, lines beginning with a full stop need to be fixed
        $text = $message->getBodyText();
        $text = str_replace("\n.", "\n..", $text);
        return $text;
    }

    /**
     * Is this a windows OS?
     *
     * @return bool
     */
    protected function isWindowsOs()
    {
        if (! $this->operatingSystem) {
            $this->operatingSystem = strtoupper(substr(PHP_OS, 0, 3));
        }
        return ($this->operatingSystem == 'WIN');
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