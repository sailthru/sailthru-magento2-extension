<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class Verifiedemails implements ArrayInterface
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
        $emails = $this->_sailthru->client->getVerifiedSenders();
        $sender_options = [ 
            ['value'=> 0, 'label'=>' '] 
        ];
        foreach ($emails as $key => $email) {
            $sender_options[] = [
                'value' => $email,
                'label' => $email
            ];
        }
        return $sender_options;
 
    }
}