<?php

namespace Sailthru\MageSail\Model;

use Magento\Framework\App\Cache;
use Magento\Framework\App\Cache\State;
use Psr\Log\LoggerInterface;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\Helper\Settings;

class SailthruTemplates
{
    const CACHE_KEY = 'saithru_templates';

    const REQUEST_ATTEMPTS_COUNT = 5;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var ClientManager
     */
    protected $settings;

    /**
     * @var bool
     */
    public $isCacheEnabled;

    public function __construct(
        Cache $cache,
        LoggerInterface $logger,
        ClientManager $clientManager,
        Settings $settings
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->clientManager = $clientManager;
        $this->settings = $settings;
    }

    /**
     * Get cache key
     *
     * @param int|null $storeId
     *
     * @return string
     */
    protected function getCacheKey($storeId = null)
    {
        if ($storeId === null) {
            return self::CACHE_KEY;
        }

        return self::CACHE_KEY . '_store_' . $storeId;
    }

    /**
     * Get cache tags
     *
     * @param int|null $storeId
     *
     * @return array
     */
    protected function getCacheTags($storeId = null)
    {
        if ($storeId === null) {
            return [$this->getCacheKey()];
        }

        return [$this->getCacheKey(), $this->getCacheKey($storeId)];
    }

    /**
     * Get Sailthru templates
     *
     * @param null $storeId
     *
     * @return array
     */
    public function getTemplatesByStoreId($storeId = null)
    {
        $cacheKey = $this->getCacheKey($storeId);
        $templates = $this->cache->load($cacheKey);
        if (!empty($templates)) {
            return unserialize($templates);
        }

        $templates = $this->loadTemplates($storeId);
        if (empty($templates)) {
            return [];
        }

        $this->cache->save(
            serialize($templates),
            $cacheKey,
            $this->getCacheTags($storeId),
            $this->settings->getTemplatesCacheLifetime()
        );

        return $templates;
    }

    protected function loadTemplates($storeId, $attempt = 1)
    {
        if ($attempt > self::REQUEST_ATTEMPTS_COUNT) {
            $this->logger->error('Request attempts is ended');

            return [];
        }

        try {
            $client = $this->clientManager->getClient(true, $storeId);

            return $client->getTemplates();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());

            return $this->loadTemplates($storeId, $attempt + 1);
        }
    }
}
