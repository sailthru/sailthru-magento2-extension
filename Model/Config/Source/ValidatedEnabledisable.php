<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Model\Config\Source;

class ValidatedEnabledisable implements \Magento\Framework\Option\ArrayInterface
{

	private $_sailthru;

    public function __construct(\Sailthru\MageSail\Helper\Api $sailthru){
        $this->_sailthru = $sailthru;
    }
    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_sailthru->isValid()){
            return [
                ['value'=>0, 'label'=>__($this->_sailthru->getInvalidMessage())]
                ];
        }
        return [['value' => 1, 'label' => __('Enable')], ['value' => 0, 'label' => __('Disable')]];
    }
}
