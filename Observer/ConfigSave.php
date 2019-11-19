<?php

namespace Sailthru\MageSail\Observer;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sailthru\MageSail\Helper\Settings;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

class ConfigSave implements ObserverInterface
{
    protected $configWriter;
    protected $request;
    protected $templateConfig;

    public function __construct(
        WriterInterface $configWriter,
        RequestInterface $request,
        TemplateConfig $templateConfig
    ) {
        $this->configWriter = $configWriter;
        $this->request = $request;
        $this->templateConfig = $templateConfig;
    }

    /**
     * Save transactional configs after saving config
     *
     * @param Observer $observer
     *
     * @return ConfigSave
     */
    public function execute(Observer $observer)
    {
        $groups = $this->request->getParam('groups');
        if (empty($groups['transactionals'])) {
            return $this;
        }

        $templates = $this->templateConfig->get('templates');
        if (empty($templates)) {
            return $this;
        }

        foreach ($templates as $template) {
            $templateId = $template['id'];
            if (!isset($groups['transactionals']['fields'][$templateId]['value'])) {
                continue;
            }

            $this->configWriter->save(
                Settings::XML_TRANSACTIONALS_PATH . $templateId,
                $groups['transactionals']['fields'][$templateId]['value']
            );
        }

        return $this;
    }
}
