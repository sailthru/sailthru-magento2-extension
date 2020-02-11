<?php

namespace Sailthru\MageSail\Mail\Queue;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\MessageQueue\PublisherInterface;
use Sailthru\MageSail\Helper\Settings as SettingsHelper;

class EmailSendPublisher
{
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
            $driverType = $this->amqpConfig->getValue(AmqpConfig::HOST)
                ? SettingsHelper::QUEUE_DRIVER_TYPE_AMQP
                : SettingsHelper::QUEUE_DRIVER_TYPE_DB;
        } catch (\LogicException $exception) {
            $driverType = SettingsHelper::QUEUE_DRIVER_TYPE_DB;
        }

        return 'sailthru.email.send.' . $driverType;
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
