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
}
