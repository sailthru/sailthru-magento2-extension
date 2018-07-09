<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Sailthru\MageSail\Model\Template as TemplateModel;

class Templates extends AbstractHelper {

    /** @var array  */
    private $templates = [];

    /** @var ClientManager  */
    private $clientManager;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ScopeResolver $scopeResolver,
        ClientManager $clientManager
    ) {
        parent::__construct($context, $storeManager, $logger, $templateModel, $templateConfig, $objectManager, $scopeResolver);
        $this->clientManager = $clientManager;
    }

    public function getSailthruTemplates($storeId = null)
    {
        if (empty($this->sailthruTemplates) or !isset($this->sailthruTemplates[$storeId])) {
            $client = $this->clientManager->getClient(true, $storeId);
            try {
                $this->templates[$storeId] = $client->getTemplates();
            } catch (\Sailthru_Client_Exception $ex) {
                $this->logger->err("Exception getting templates: {$ex->getMessage()}");
            }
        }

        return $this->templates[$storeId];
    }

    public function templateExists($templateIdentifier, $storeId = null)
    {
        $templates = $this->getSailthruTemplates($storeId);
        if (isset($templates['templates'])) {
            $templates = array_column($templates['templates'], 'name');
            return in_array($templateIdentifier, $templates);
        }
        return false;
    }

    /**
     * To create template in Sailthru.
     *
     * @param  string $templateIdentifier
     * @param  string $sender
     */
    public function saveTemplate($templateIdentifier, $sender, $storeId)
    {
        $data = [
            "content_html" => "{content} {beacon}",
            "subject" => "{subj}",
            "from_email" => $sender,
            "is_link_tracking" => 1
        ];

        $client = $this->clientManager->getClient(true, $storeId);
        try {
            $response = $client->saveTemplate($templateIdentifier, $data);
            if (isset($response['error']))
                $client->logger($response['errormsg']);
        } catch (\Exception $e) {
            $client->logger($e->getMessage());
        }
    }


}