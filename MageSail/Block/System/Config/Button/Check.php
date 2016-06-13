<?php

namespace Sailthru\MageSail\Block\System\Config\Button;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Check extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
    * Sailthru object 
    * @var \Sailthru\MageSail\Helper\Api
    */
    protected $_sailthru;



    public function __construct(\Magento\Backend\Block\Template\Context $context, \Sailthru\MageSail\Helper\Api $sailthru, array $data = [])
    {
        $this->_sailthru = $sailthru;
        parent::__construct($context, $data);
    }


    /**
     * Path to block template
     */
    const CHECK_TEMPLATE = 'system/validateapi.phtml';
    

    /**
     * Set template to itself
     *
     * @return \Sailthru\MageSail\Block\Adminhtml\System\Config\Validateapi
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::CHECK_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }



    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : 'Validate Credentials';
        $settings = $this->_sailthru->apiValidate();
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'api_key_id' => $originalData['api_key_id'],
                'intern_url' => $this->getUrl($originalData['button_url']),
                'load_config_url' => $this->getUrl($originalData['load_config_url']),
                'html_id' => $element->getHtmlId(),
                'sailthru' => $settings
            ]
        );
        return $this->_toHtml();
    }
}
