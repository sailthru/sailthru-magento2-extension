<?php

namespace Sailthru\MageSail\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Sailthru\MageSail\Helper\ClientManager;

class Js extends Template
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @param Template\Context $context
     * @param ClientManager $clientManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ClientManager $clientManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->clientManager = $clientManager;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->clientManager->getCustomerId();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->clientManager->useJs()) {
            return parent::_toHtml();
        }
        return '';
    }
}
