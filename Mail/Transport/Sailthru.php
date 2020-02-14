<?php

namespace Sailthru\MageSail\Mail\Transport;

use Magento\Framework\Exception\MailException;
use Sailthru\MageSail\Helper\ClientManager as ClientManagerHelper;
use Sailthru\MageSail\Helper\Settings as SettingsHelper;
use Sailthru\MageSail\Helper\Templates as TemplatesHelper;

class Sailthru extends \Magento\Framework\DataObject
{
    /**
     * @var ClientManagerHelper
     */
    protected $clientManagerHelper;

    /**
     * @var SettingsHelper
     */
    protected $settingsHelper;

    /**
     * @var TemplatesHelper
     */
    protected $templatesHelper;

    /**
     * SailthruTransport constructor.
     *
     * @param ClientManagerHelper $clientManagerHelper
     * @param SettingsHelper $settingsHelper
     * @param TemplatesHelper $templatesHelper
     * @param array $data
     */
    public function __construct(
        ClientManagerHelper $clientManagerHelper,
        SettingsHelper $settingsHelper,
        TemplatesHelper $templatesHelper,
        array $data
    ) {
        $this->clientManagerHelper = $clientManagerHelper;
        $this->settingsHelper = $settingsHelper;
        $this->templatesHelper = $templatesHelper;

        parent::__construct($data);
    }

    /**
     * Prepare params for API request
     *
     * @param $templateData
     * @param $emailData
     * @param $storeId
     *
     * @return array
     */
    protected function prepareParams($templateData, $emailData, $storeId)
    {
        $vars = [
            'subj'    => $emailData['subject'],
            'content' => $emailData['content'],
        ];

        $template = $this->settingsHelper->getTemplateName($templateData['identifier'], $storeId);
        $vars += $this->settingsHelper->getTemplateAdditionalVariables(
            $template['orig_template_code'],
            $templateData['variables']
        );

        $templateName = $template['name'];
        if (!$this->templatesHelper->templateExists($templateName, $storeId)) {
            $this->templatesHelper->saveTemplate(
                $templateName,
                $this->settingsHelper->getSender($storeId),
                $storeId
            );
        }

        $params = [
            'template' => $templateName,
            'email'    => $emailData['to'],
            'vars'     => $vars,
        ];

        return $params;
    }

    /**
     * Validate data params
     *
     * @return Sailthru
     *
     * @throws MailException
     */
    protected function validate()
    {
        if (empty($this->getTemplateData())) {
            throw new MailException(__('Template data is empty'));
        }
        if (empty($this->getEmailData())) {
            throw new MailException(__('Email data is empty'));
        }
        $storeId = (int)$this->getStoreId();
        if ($storeId !== 0 && empty($this->getStoreId())) {
            throw new MailException(__('Template data is empty'));
        }

        return $this;
    }

    /**
     * Send mail via Sailthru API
     *
     * @return Sailthru
     *
     * @throws \Exception
     */
    public function sendMessage()
    {
        try {
            $this->validate();
            $templateData = $this->getTemplateData();
            $emailData = $this->getEmailData();
            $storeId = (int)$this->getStoreId();

            $client = $this->clientManagerHelper->getClient(true, $storeId);
            $response = $client->apiPost('send', $this->prepareParams($templateData, $emailData, $storeId));
            if (isset($response["error"])) {
                $client->logger($response['errormsg']);
                throw new MailException(__($response['errormsg']));
            }
        } catch (\Exception $e) {
            throw new MailException("Couldn't send the mail {$e->getMessage()}");
        }

        return $this;
    }
}
