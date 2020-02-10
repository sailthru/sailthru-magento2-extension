<?php

declare(strict_types=1);

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Sailthru\MageSail\Mail\Queue\EmailSendPublisher;

class Queue extends AbstractHelper
{
    /**
     * @var Settings
     */
    protected $settingsHelper;

    /**
     * @var MessageEncoder
     */
    protected $messageEncoder;

    /**
     * @var EmailSendPublisher
     */
    protected $emailSendPublisher;

    public function __construct(
        Context $context,
        Settings $settingsHelper,
        MessageEncoder $messageEncoder,
        EmailSendPublisher $emailSendPublisher
    ) {
        $this->settingsHelper = $settingsHelper;
        $this->messageEncoder = $messageEncoder;
        $this->emailSendPublisher = $emailSendPublisher;

        parent::__construct($context);
    }

    /**
     * Republish message and edit count attempt in message data
     *
     * @param EnvelopeInterface $message
     *
     * @return Queue
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function republishMessage(EnvelopeInterface $message): Queue
    {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $decodedMessage = $this->messageEncoder->decode($topicName, $message->getBody());
        if (!is_string($decodedMessage)) {
            return $this;
        }
        $decodedMessage = json_decode($decodedMessage, true);
        if (!is_array($decodedMessage)) {
            return $this;
        }

        $decodedMessage['attempts'] = !empty($decodedMessage['attempts']) ? $decodedMessage['attempts'] + 1 : 1;
        $maxNumberOfTrials = $this->settingsHelper
            ->getTransactionalsProcessQueueAttempts($decodedMessage['store_id']);
        if ($decodedMessage['attempts'] >= $maxNumberOfTrials) {
            return $this;
        }
        $this->emailSendPublisher->execute($decodedMessage);

        return $this;
    }
}
