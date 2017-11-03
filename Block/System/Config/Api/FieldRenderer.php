<?php

namespace Sailthru\MageSail\Block\System\Config\Api;

use Sailthru\MageSail\Helper\Api;

class FieldRenderer extends \Magento\Config\Block\System\Config\Form\Field
{
    /** List of element ids to replace with error message. */
    const IDS = [
        'magesail_content_intercept_enable_intercept',
        'magesail_content_tags_use_seo',
        'magesail_lists_lists_enable_signup_list',
        'magesail_send_abandoned_cart_enabled',
        'magesail_send_transactionals_send_through_sailthru',
    ];

    /** @var bool */
    private $apiEnabled;

    /**
     * Field renderer construct.
     * 
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Api                                     $apiHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Api $apiHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiEnabled = $apiHelper->isValid() ? true : false;
    }

    /**
     * Retrieve HTML markup for given form element.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * 
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->apiEnabled ? parent::render($element) : self::renderError(
            $element->getId(),
            'API Validation Failed.',
            'After saving valid credentials, other settings will be accessible.'
        );
    }

    /**
     * To return error message html.
     * 
     * @param  string      $elementId
     * @param  string      $error
     * @param  string|null $note
     * 
     * @return mixed
     */
    private function renderError($elementId, $error, $note = null)
    {
        if (in_array($elementId, self::IDS)) {
            $message = '<div><center><h1>Error</h1><h3>' . $error . '</h3></center></div>';
            if ($note)
                $message .= '<div><center><p class="note"><span>' . $note . '</span></p></center></div>';    
        } else {
            $message = null;
        }      

        return $message;
    }
}
