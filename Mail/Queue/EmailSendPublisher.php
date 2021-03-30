<?php

namespace Sailthru\MageSail\Mail\Queue;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\MessageQueue\PublisherInterface;
use Sailthru\MageSail\Helper\Settings as SettingsHelper;

class EmailSendPublisher
{
    /**
     * Topic names
     */
    const TOPIC_NAME_DB   = 'sailthru.email.send.db';
    const TOPIC_NAME_AMQP = 'sailthru.email.send.amqp';

    /**
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * EmailSendPublisher constructor.
     *
     * @param AmqpConfig $amqpConfig
     * @param PublisherInterface $publisher
     */
    public function __construct(
        AmqpConfig $amqpConfig,
        PublisherInterface $publisher
    ) {
        $this->amqpConfig = $amqpConfig;
        $this->publisher = $publisher;
    }

    /**
     * Get topic name
     *
     * @return string
     */
    public function getTopicName()
    {
        try {
            return $this->isAmqpConfigured() ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
        } catch (\LogicException $exception) {
            return self::TOPIC_NAME_DB;
        }
    }

    /**
     * Check Amqp is configured.
     *
     * @return bool
     *
     * @throws \LogicException
     */
    protected function isAmqpConfigured()
    {
        return $this->amqpConfig->getValue(AmqpConfig::HOST) ? true : false;
    }

    /**
     * Add message to queue
     *
     * @param array $messageData
     * @param int $attempt
     *
     * @return $this
     */
    public function execute(array $messageData, int $attempt = 0)
    {
        if ($attempt >= SettingsHelper::QUEUE_ATTEMPTS_COUNT) {
            return $this;
        }
        $messageData['attempt'] = $attempt;
        $this->publisher->publish($this->getTopicName(), json_encode($messageData));

        return $this;
    }
}
