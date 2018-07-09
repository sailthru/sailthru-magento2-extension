<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Store\Api\Data\StoreInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /** @var Message */
    protected $message;

    /**
     * Prepare message
     *
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Zend_Mail_Exception
     */
    protected function prepareMessage()
    {
        /** @var Template $template */
        $template = $this->getTemplate();
        $types = [
            TemplateTypesInterface::TYPE_TEXT => MessageInterface::TYPE_TEXT,
            TemplateTypesInterface::TYPE_HTML => MessageInterface::TYPE_HTML,
        ];

        $body = $template->processTemplate();

        /** @customization START */
        $templateData = [
            'variables' => $template->templateVariables ?: [],
            'identifier' => $this->templateIdentifier,
        ];

        if(isset($this->templateVars['store'])){
            /** @var StoreInterface $store */
            $store = $this->templateVars['store'];
            $templateData['storeId'] = $store->getId();
        }

        $this->message->setTemplateInfo($templateData);
        /** @customization END */

        $this->message->setMessageType($types[$template->getType()])
            ->setBody($body)
            ->setSubject($template->getSubject());

        return $this;
    }
}
