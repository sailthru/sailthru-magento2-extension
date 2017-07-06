<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;
use \Sailthru\MageSail\Helper\ClientManager;
use \Sailthru\MageSail\Helper\Settings as SailthruSettings;

class Tagsources implements ArrayInterface
{

    /** @var ClientManager  */
    private $clientManager;

    public function __construct(ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    public function toOptionArray()
    {
        if (!$this->clientManager->isValid()) {
            return [
                ['value'=>0, 'label'=>__(SailthruSettings::SOURCE_MODEL_VALIDATION_MSG)]
            ];
        }

        return [
            ['label' => 'SEO Keywords', 'value' => Sailthru::TAGS_SEO_KEYS, 'name'=> '1' ],
            ['label' => 'Categories', 'value' => Sailthru::TAGS_CATEGORIES, 'name'=> '2' ],
            ['label' => 'Attributes', 'value' => Sailthru::TAGS_ATTRIBUTES, 'name' => '3' ],
        ];
    }
}
