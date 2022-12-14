<?php

namespace Sailthru\MageSail\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManager;
use Sailthru\MageSail\Logger;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Sailthru\MageSail\Model\Template as TemplateModel;
use Sailthru\MageSail\Model\SailthruTemplates;

class Templates extends AbstractHelper
{

    /** @var array */
    private $templates = [];

    /** @var ClientManager */
    private $clientManager;

    /** @var SailthruTemplates */
    protected $sailthruTemplates;

    public function __construct(
        Context $context,
        StoreManager $storeManager,
        Logger $logger,
        TemplateModel $templateModel,
        TemplateConfig $templateConfig,
        ObjectManagerInterface $objectManager,
        ScopeResolver $scopeResolver,
        ClientManager $clientManager,
        SailthruTemplates $sailthruTemplates
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $logger,
            $templateModel,
            $templateConfig,
            $objectManager,
            $scopeResolver
        );
        $this->clientManager = $clientManager;
        $this->sailthruTemplates = $sailthruTemplates;
    }

    public function getSailthruTemplates($storeId = null)
    {
        if (!empty($this->templates[$storeId])) {
            return $this->templates[$storeId];
        }

        try {
            $this->templates[$storeId] = $this->sailthruTemplates->getTemplatesByStoreId($storeId);

            return $this->templates[$storeId];
        } catch (\Sailthru_Client_Exception $ex) {
            $this->logger->error("Exception getting templates: {$ex->getMessage()}");

            return [];
        }
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
     * @param string   $templateIdentifier
     * @param string   $sender
     * @param null|int $storeId
     */
    public function saveTemplate($templateIdentifier, $sender, $storeId = null)
    {
        $data = [
            'content_html'     => '{content} {beacon}',
            'subject'          => '{subj}',
            'from_email'       => $sender,
            'is_link_tracking' => 1
        ];

        $client = $this->clientManager->getClient($storeId);
        try {
            $response = $client->saveTemplate($templateIdentifier, $data);
            if (isset($response['error'])) {
                $client->logger($response['errormsg']);
            }
        } catch (\Exception $e) {
            $client->logger($e->getMessage());
        }
    }

}
