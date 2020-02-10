<?php

namespace Sailthru\MageSail\Plugin\Queue;

use \Magento\MessageQueue\Model\LockFactory;
use \Magento\MessageQueue\Model\ResourceModel\Lock;
use \Magento\Framework\MessageQueue\LockInterface;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class LockResourceModelIntercept
{
    /**
     * @var LockFactory
     */
    protected $lockFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * LockResourceModelIntercept constructor.
     * @param LockFactory $lockFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        LockFactory $lockFactory,
        DateTime $dateTime
    ) {
        $this->lockFactory = $lockFactory;
        $this->dateTime = $dateTime;
    }

    public function aroundSaveLock(Lock $subject, callable $proceed, LockInterface $lock)
    {
        $object = $this->lockFactory->create();
        $object->setMessageCode($lock->getMessageCode());
        $object->setCreatedAt($this->dateTime->gmtTimestamp());
        $object->save();
        /**
         * Temporary fix: https://github.com/magento/magento2/issues/18140
         * @customization START
         */
        $lock->setId($object->getId());
        /** @customization END */
    }
}
