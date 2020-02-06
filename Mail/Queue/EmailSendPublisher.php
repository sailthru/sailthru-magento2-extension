<?php

namespace Sailthru\MageSail\Mail\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;

class EmailSendPublisher
{
    const TOPIC_NAME = 'sailthru.email.send';

    protected $publisher;

    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function execute($templateData, $emailData, $storeId)
    {
        $this->publisher->publish(
            self::TOPIC_NAME,
            json_encode([
                'template_data' => $templateData,
                'email_data'    => $emailData,
                'store_id'      => $storeId,
            ])
        );

        return $this;
    }
}
