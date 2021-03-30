<?php

namespace Sailthru\MageSail\Mail\Queue;

use Psr\Log\LoggerInterface;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\Mail\Transport\Sailthru as SailthruTransport;
use Sailthru\MageSail\Mail\Transport\SailthruFactory as SailthruTransportFactory;

class EmailSendProcessor
{
    /**
     * @var EmailSendPublisher
     */
    protected $publisher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Settings
     */
    protected $settingsHelper;

    /**
     * @var SailthruTransportFactory
     */
    protected $sailthruTransportFactory;

    public function __construct(
        EmailSendPublisher $publisher,
        LoggerInterface $logger,
        Settings $settingsHelper,
        SailthruTransportFactory $sailthruTransportFactory
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->settingsHelper = $settingsHelper;
        $this->sailthruTransportFactory = $sailthruTransportFactory;
    }

    /**
     * Init and return transport
     *
     * @param array $data
     *
     * @return SailthruTransport
     */
    public function getTransport(array $data)
    {
        return $this->sailthruTransportFactory->create(['data' => $data]);
    }

    /**
     * Consumer handler of send Sailthru email
     *
     * @param string $messageData
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function execute($messageData)
    {
        $decodedData = json_decode($messageData, true);
        try {
            $this->getTransport($decodedData)->sendMessage();
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage());
            $this->publisher->execute($decodedData, ($decodedData['attempt'] ?? 0) + 1);
        }

        return $this;
    }
}
