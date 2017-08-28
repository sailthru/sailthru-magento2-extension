<?php

namespace Sailthru\MageSail\Block\System\Config\Api;

use Sailthru\MageSail\Helper\ClientManager;

class Check extends \Magento\Config\Block\System\Config\Form\Field
{
    /** @var ClientManager  */
    private $clientManager;

    /** @var bool */
    private $apiEnabled;

    /**
     * Ids of error fields in tabs.
     */
    const IDS = [
        'magesail_lists_lists_display_error', # lists_lists
        'magesail_send_transactionals_display_error', # send_transactionals
        'magesail_send_abandoned_cart_display_error', # send_abandoned_cart
        'magesail_content_intercept_display_error', # content_intercept
        'magesail_content_tags_display_error', # content_tags
    ];

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ClientManager $clientManager,
        array $data = []
    ) {
        $this->clientManager = $clientManager;
        parent::__construct($context, $data);

        $apiValidate = $this->clientManager->apiValidate();

        $this->apiEnabled = $apiValidate[0] == 1
            ? true
            : false;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * 
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->apiEnabled) {
            return in_array($element->getId(), self::IDS)
                ? null
                : parent::render($element);
        } else {
            return in_array($element->getId(), self::IDS)
                ? self::renderError(
                    'API Validation Failed.',
                    'After saving valid credentials, other settings will be accessible.'
                ) : null;
        }
    }

    /**
     * To return error message.
     * 
     * @param  string $error
     * 
     * @return string
     */
    private function renderError($error, $note = null)
    {
        $message = '<div><center><h1>Error</h1><h3>'.$error.'</h3></center></div>';
        if ($note)
            $message .= '<div><center><p class="note"><span>'.$note.'</span></p></center></div>';

        return $message;
    }
}
