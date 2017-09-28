<?php

namespace Sailthru\MageSail\Model\Config\Template;

use Magento\Framework\Config\Data as DataConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Config\CacheInterface;
use Sailthru\MageSail\Model\Config\Template\TemplateReader;


class Data extends DataConfig
{
    private $templates = [];

    public function __construct(
        TemplateReader $reader,
        CacheInterface $cache,
        StoreManagerInterface $storeManager
    ) {
        $generatedCacheId = md5($storeManager->getStore()->getId());
        parent::__construct(
            $reader,
            $cache,
            $generatedCacheId
        );
    }

    /**
     * Get config value by key
     *
     * @param  string        $path
     * @param  null|string   $default
     * 
     * @return array|null
     */
    public function get($path = null, $default = null)
    {
        if (empty($this->templates)) {
            $this->templates = parent::get($path, $default);
        }

        return $this->templates;
    }
}
