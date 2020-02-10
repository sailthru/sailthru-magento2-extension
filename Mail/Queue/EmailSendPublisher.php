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

    /**
     * Add message to queue
     *
     * @param array $messageData
     *
     * @return $this
     */
    public function execute(array $messageData)
    {
        $this->publisher->publish(self::TOPIC_NAME, json_encode($messageData));

        return $this;
    }
}
