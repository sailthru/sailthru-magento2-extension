<?php

namespace Sailthru\MageSail\Mail;

use Sailthru\MageSail\Mail\EmailMessage;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\Data\StoreInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /** @var EmailMessage */
    protected $message;

    /** @var array */
    protected $templateData;

    /** @var TemplateInterface */
    protected $template;

    /**
     * Get template
     *
     * @return TemplateInterface
     *
     * @customization START
     */
    protected function getTemplate()
    {
        /** @var Template $template */
        $this->template = parent::getTemplate();

        return $this->template;
    }
    /** @customization END */

    /**
     * Prepare message.
     *
     * @return TransportBuilder
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @customization START
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();
        $this->templateData = [
            'variables'  => $this->template->templateVariables ?: [],
            'identifier' => $this->templateIdentifier,
        ];

        if (isset($this->templateVars['store'])){
            /** @var StoreInterface $store */
            $store = $this->templateVars['store'];
            $this->templateData['storeId'] = $store->getId();
        }

        // Newsletter admin patch
        if (isset($this->templateVars['subscriber'])) {
            /** @var Subscriber $subscriber */
            $subscriber = $this->templateVars['subscriber'];
            $storeId = $subscriber->getStoreId();
            $this->templateData['storeId'] = $storeId;
        }
        $this->message->setTemplateInfo($this->templateData);

        return $this;
    }
    /** @customization END */
}
