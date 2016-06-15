<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class Sailthrulists implements ArrayInterface
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
                ['value'=>0, 'label'=>__('Please Enter Valid Credentials')]
                ];
        }

        $data = $this->_sailthru->client->getLists();
        $lists = $data["lists"];
        $lists_options = array();
        foreach ($lists as $list) {
            if ($list['type'] == 'normal')
            {
                $lists_options[] = [
                    'value' => $list['name'],
                    'label' => __("{$list['name']} ({$list['email_count']} Emails)")
                ];
            }
        }

        return $lists_options;
 
        // return [
        //     ['value' => 0, 'label' => __('Zero')],
        //     ['value' => 1, 'label' => __('One')],
        //     ['value' => 2, 'label' => __('Two')],
        // ];
    }
}