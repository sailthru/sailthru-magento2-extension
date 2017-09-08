<?php

namespace Sailthru\MageSail\Model\Config\Template;

use Magento\Framework\Config\Data as DataConfig;


class Data extends DataConfig
{
    private $templates = [];

    /**
     * Get config value by key
     *
     * @param string $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get($path = null, $default = null)
    {
        if (empty($this->templates)) {
            $this->templates = parent::get($path, $default);
        }

        return $this->templates;
    }
}
