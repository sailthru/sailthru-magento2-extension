<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;
use \Sailthru\MageSail\Helper\ClientManager;
use \Sailthru\MageSail\Helper\Settings as SailthruSettings;

class Verifiedemails implements ArrayInterface
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
                ['value'=>0, 'label'=>__('Please Enter Valid Credentials')]
                ];
        }
        $emails = $this->clientManager->getClient()->getVerifiedSenders();
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
