<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;
use \Sailthru\MageSail\Helper\ClientManager;
use \Sailthru\MageSail\Helper\Settings as SailthruSettings;

class Sailthrutemplates implements ArrayInterface
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
        $data = $this->clientManager->getClient()->getTemplates();
        $templates = $data["templates"];
        $tpl_options = [
            ['value'=> 0, 'label'=>' ']
        ];
        foreach ($templates as $tpl) {
                $tpl_options[] = [
                    'value' => $tpl['name'],
                    'label' => __($tpl['name'])
                ];
        }
        return $tpl_options;
    }
}
