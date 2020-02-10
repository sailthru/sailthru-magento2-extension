<?php

namespace Sailthru\MageSail\Model\Driver;

use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\MysqlMq\Model\Driver\Queue;
use Magento\MysqlMq\Model\QueueManagement;
use Psr\Log\LoggerInterface;
use Sailthru\MageSail\Helper\Settings as SettingsHelper;

class MysqlMq extends Queue
{
    protected $queueName;

    /**
     * MysqlMqQueue constructor.
     * @param QueueManagement $queueManagement
     * @param EnvelopeFactory $envelopeFactory
     * @param SettingsHelper $settingsHelper
     * @param LoggerInterface $logger
     * @param string $queueName
     * @param int $interval
     * @param int $maxNumberOfTrials
     *
     * @customization START
     */
    public function __construct(
        QueueManagement $queueManagement,
        EnvelopeFactory $envelopeFactory,
        SettingsHelper $settingsHelper,
        LoggerInterface $logger,
        string $queueName,
        int $interval = 5,
        int $maxNumberOfTrials = 3
    ) {
        if ($queueName == 'sailthru_email_send') {
            $maxNumberOfTrials = $settingsHelper->getTransactionalsProcessQueueAttempts();
        }

        parent::__construct($queueManagement, $envelopeFactory, $logger, $queueName, $interval, $maxNumberOfTrials);
    }
    /** @customization END */
}
