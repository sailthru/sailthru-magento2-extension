<?php
/**
 * Mail Message
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

class Message extends \Magento\Framework\Mail\Message
{
    /**
     * Template info.
     *
     * @var string
     **/
    public $templateInfo = [];

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
