<?php

namespace Sailthru\MageSail\Mail\Queue;

use Psr\Log\LoggerInterface;
use Sailthru\MageSail\Helper\Settings;
use Magento\Framework\Mail\TransportInterface;

class ConsumerEmailSend
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Settings
     */
    protected $settingsHelper;

    /**
     * @var TransportInterface
     */
    protected $transport;

    public function __construct(
        LoggerInterface $logger,
        Settings $settingsHelper,
        TransportInterface $transport
    ) {
        $this->logger = $logger;
        $this->settingsHelper = $settingsHelper;
        $this->transport = $transport;
    }

    /**
     * Consumer handler of send Sailthru email
     *
     * @param $data
     *
     * @return $this
     */
    public function execute($data)
    {
        $countAttempt = $this->settingsHelper->getTransactionalsProcessQueueAttempts($data['storeId']);
        while ($countAttempt) {
            try {
                $data = json_decode($data, true);
                $this->transport->sendViaAPI($data['templateData'], $data['emailData'], $data['storeId']);
                break;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $countAttempt--;
            }
        }

        return $this;
    }
}
