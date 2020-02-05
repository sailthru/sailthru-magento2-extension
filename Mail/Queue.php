<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\MessageQueue\PublisherInterface;

class Queue
{
    const TOPIC_NAME = 'sailthru.email.send';

    protected $publisher;

    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function publishEmail($templateData, $emailData, $storeId)
    {
        $this->publisher->publish(
            self::TOPIC_NAME,
            json_encode([
                'templateData' => $templateData,
                'emailData'    => $emailData,
                'storeId'      => $storeId,
            ])
        );

        return $this;
    }
}
