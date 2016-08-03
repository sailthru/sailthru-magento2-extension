<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Sailthru\MageSail\Helper\Api as Sailthru;
use \Magento\Framework\Option\ArrayInterface;

class Tagsources implements ArrayInterface
{

    public function __construct(Sailthru $sailthru){
        $this->sailthru = $sailthru;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->sailthru->isValid()){
            return [
                ['value'=>0, 'label'=>__('Please Enter Valid Credentials')]
                ];
        }

        return [
            ['label' => 'SEO Keywords', 'value' => Sailthru::TAGS_SEO_KEYS, 'name'=> '1' ],
            ['label' => 'Categories', 'value' => Sailthru::TAGS_CATEGORIES, 'name'=> '2' ],
            ['label' => 'Attributes', 'value' => Sailthru::TAGS_ATTRIBUTES, 'name' => '3' ],
        ]; 
    }
}