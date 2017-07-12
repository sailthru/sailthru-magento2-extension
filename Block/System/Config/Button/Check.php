<?php

namespace Sailthru\MageSail\Block\System\Config\Button;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Sailthru\MageSail\Helper\ClientManager;

class Check extends \Magento\Config\Block\System\Config\Form\Field
{

    /** @var ClientManager  */
    private $clientManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ClientManager $clientManager,
        array $data = []
    ) {
        $this->clientManager = $clientManager;
        parent::__construct($context, $data);
    }

    /**
     * Path to block template
     */
    const CHECK_TEMPLATE = 'system/validateapi.phtml';

    /**
     * Set template to itself
     *
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
        $api_validate = $this->clientManager->apiValidate();
        if ($api_validate[0] == 1) {
            $data = [
                'class' => 'sail_success',
                'status' => 'API Validation Complete',
            ];
        } else {
            $data = [
                'class' => 'sail_fail',
                'status'  => 'API Validation Failed'];
        }
        $data['message'] = $api_validate[1];
        $data['html_id'] = $element->getHtmlId();
        $data['success'] = $api_validate[0];
        $data['original_data'] = $originalData;
        $this->addData($data);
        return $this->_toHtml();
    }
}
