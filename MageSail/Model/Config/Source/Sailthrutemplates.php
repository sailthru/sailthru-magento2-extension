<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class Sailthrutemplates implements ArrayInterface
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

        $data = $this->_sailthru->client->getTemplates();
        $templates = $data["templates"];
        $tpl_options = array();
        foreach ($templates as $tpl) {
                $tpl_options[] = [
                    'value' => $tpl['name'],
                    'label' => __($tpl['name'])
                ];
        }

        return $tpl_options;
 
    }
}