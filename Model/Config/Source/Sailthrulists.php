<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class Sailthrulists implements ArrayInterface
{

    private $_sailthru;

    public function __construct(\Sailthru\MageSail\Helper\Api $sailthru)
    {
        $this->_sailthru = $sailthru;
    }

    public function toOptionArray()
    {
        if (!$this->_sailthru->isValid()) {
            return [
                ['value'=>0, 'label'=>__('Please Enter Valid Credentials')]
                ];
        }

        $data = $this->_sailthru->client->getLists();
        $lists = $data["lists"];
        $lists_options = [
            ['value'=> 0, 'label'=>' ']
        ];
        foreach ($lists as $list) {
            if ($list['type'] == 'normal') {
                $lists_options[] = [
                    'value' => $list['name'],
                    'label' => __("{$list['name']} ({$list['email_count']} Emails)")
                ];
            }
        }

        return $lists_options;
    }
}
