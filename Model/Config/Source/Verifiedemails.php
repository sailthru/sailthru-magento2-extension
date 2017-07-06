<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

class VerifiedEmails extends AbstractSource
{

    protected function getDisplayData()
    {
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
