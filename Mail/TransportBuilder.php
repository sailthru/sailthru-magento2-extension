<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Store\Model\Store;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * Prepare message
     *
     * @return $this
     */
    protected function prepareMessage()
    {
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
            /** @var Store $store */
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
