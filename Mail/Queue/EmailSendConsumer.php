<?php

namespace Sailthru\MageSail\Mail\Queue;

use Psr\Log\LoggerInterface;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\Mail\Transport\Sailthru as SailthruTransport;
use Sailthru\MageSail\Mail\Transport\SailthruFactory as SailthruTransportFactory;

class EmailSendConsumer
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
    public function getTransport($data)
    {
        return $this->sailthruTransportFactory->create(['data' => $data]);
    }

    /**
     * Consumer handler of send Sailthru email
     *
     * @param string $data
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function execute($data)
    {
        $data = json_decode($data, true);
        $this->getTransport($data)->sendMessage();

        return $this;
    }
}
