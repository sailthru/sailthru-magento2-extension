<?php

namespace Sailthru\MageSail\Mail;

class EmailMessage extends \Magento\Framework\Mail\EmailMessage
{
    /**
     * Template info.
     *
     * @var array
     **/
    private $templateInfo = [];

    /**
     * To get info about template.
     *
     * @return array
     **/
    public function getTemplateInfo()
    {
        return $this->templateInfo;
    }

    /**
     * To set info about template.
     *
     * @param array $info
     *
     * @return bool
     **/
    public function setTemplateInfo(array $info)
    {
        if ($info) {
            $this->templateInfo = $info;

            return true;
        }

        return false;
    }

    /**
     * Get decoded MIME body text
     *
     * Handles both Symfony MIME (Magento 2.4.8-p3+) and legacy multipart messages
     *
     * @return string
     */
    public function getDecodedBodyText()
    {
        $body = $this->getBody();

        if (empty($body)) {
            return '';
        }

        if ($body instanceof \Symfony\Component\Mime\Part\TextPart) {
            return $body->getBody();
        }

        if (method_exists($body, 'getParts')) {
            $parts = $body->getParts();
            if (!empty($parts[0])) {
                return $parts[0]->getRawContent() ?? '';
            }
        }

        return (string) $body;
    }
}
