<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;
use \Sailthru\MageSail\Helper\ClientManager;
use \Sailthru\MageSail\Helper\Settings as SailthruSettings;

class Sailthrulists implements ArrayInterface
{

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

        $data = $this->clientManager->getClient()->getLists();
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
