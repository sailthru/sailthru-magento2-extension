<?php

namespace Sailthru\MageSail\Plugin;

use \Magento\Framework\Exception\MailException;
use \Magento\Config\Model\Config\Structure\Element\Group as OriginalGroup;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Sailthru\MageSail\Helper\ClientManager;
use Sailthru\MageSail\MageClient;
use Sailthru\MageSail\Helper\Settings;

class Group
{
    const REQUIRED_GROUP_ID = 'transactionals';

    /**
     * @var \Sailthru\MageSail\Model\Config\Template\Data
     **/
    protected $templateConfig;

    /**
     * @var \Sailthru\MageSail\Helper\ClientManager
     */
    protected $clientManager;

    /**
     * @var MageClient
     */
    protected $client;

    /**
     * @var \Sailthru\MageSail\Helper\Settings
     */
    protected $sailthruSettings;

    /**
     * Group constructor.
     * 
     * @param TemplateConfig $templateConfig
     */
    public function __construct(
        TemplateConfig $templateConfig,
        ClientManager $clientManager,
        Settings $sailthruSettings
    )
    {
        $this->templateConfig = $templateConfig;
        $this->clientManager = $clientManager;
        $this->client = $clientManager->getClient();
        $this->sailthruSettings = $sailthruSettings;
    }

    /**
     * Add dynamic config fields for each template
     *
     * @param OriginalGroup $subject
     * @param callable $proceed
     * @param array $data
     * @param $scope
     * 
     * @return mixed
     */
    public function aroundSetData(
        OriginalGroup $subject,
        callable $proceed,
        array $data,
        $scope
    ) {
        # This method runs for every group.
        # Add a condition to check for the one to which we're
        # interested in adding fields.
        if(self::REQUIRED_GROUP_ID == $data['id']) {
            $apiValidate = $this->clientManager->apiValidate();
            if (isset($apiValidate[0]) && 1 == $apiValidate[0]) {
                $dynamicFields = self::getDynamicConfigFields();
                if(!empty($dynamicFields)) {
                    $data['children'] += $dynamicFields;
                }
            }
        }

        return $proceed($data, $scope);
    }

    /**
     * To get field list.
     * 
     * @return array $fields
     */
    protected function getDynamicConfigFields()
    {
        $fields = [];
        $templateList = $this->templateConfig->get('templates');
        if ($templateList) {
            $apiTemplates = $this->client->getTemplates();
            $sailthruTemplates = isset($apiTemplates['templates'])
                ? array_column($apiTemplates['templates'], 'name')
                : [];
            
            # Creates the `Magento Generic` template if it doesn't exist.
            $sender = $this->sailthruSettings->getSender();
            if ($sender && !in_array('Magento Generic', $sailthruTemplates)) {
                try {
                    $response = $this->client->saveTemplate(
                        'Magento Generic',
                        [
                            "content_html" => "{content} {beacon}",
                            "subject" => "{subj}",
                            "from_email" => $sender,
                            "is_link_tracking" => 1
                        ]
                    );

                    if (isset($response['error'])) {
                        $this->client->logger($response['errormsg']);
                    }
                } catch (\Exception $e) {
                    $this->client->logger($e->getMessage());
                }
            }
            
            foreach ($templateList as $template) {
                # Creates the `specific` template if it doesn't exists.
                if ($sender && !in_array('magento_'.$template['id'], $sailthruTemplates)) {
                    try {
                        $response = $this->client->saveTemplate(
                            'magento_'.$template['id'],
                            [
                                'subject' => '{subj}',
                                'content_html' => '{content}',
                                'from_email' => $sender,
                            ]
                        );

                        if (isset($response['error'])) {
                            $this->client->logger($response['errormsg']);
                        }
                    } catch (\Exception $e) {
                        $this->client->logger($e->getMessage());
                    }
                }

                $enabledField = self::addField($template, 'enabled');
                $tmpListField = self::addField($template, 'template_list');

                $fields[$enabledField['id']] = $enabledField;
                $fields[$tmpListField['id']] = $tmpListField;
            }
        }

        return $fields;
    }

    /**
     * To add a field.
     * 
     * @param   array  $params
     * @param   string $type
     *
     * @return  array
     */
    protected function addField($params, $type)
    {
        # Each dynamic field always depends from send_through_sailthru.
        $depends = [
            'fields' => [
                '*/*/send_through_sailthru' => [
                    'id' => 'magesail_send/transactionals/send_through_sailthru',
                    'value' => '1',
                    '_elementType' => 'field',
                    'dependPath' => [
                        0 => 'magesail_send',
                        1 => 'transactionals',
                        2 => 'send_through_sailthru',
                    ],
                ],
            ],
        ];
        
        if ('enabled' == $type) {
            $id = isset($params['id']) ? $params['id'].'_enabled' : '';
            $label = isset($params['name']) ? 'Override Magento '.$params['name'] : 'Default label';
            $sourceModel = isset($params['enabled_model']) ? $params['enabled_model'] : null;
        } else {
            $id = isset($params['id']) ? $params['id'] : '';
            $idEnabled = isset($params['id']) ? $params['id'].'_enabled' : '';
            $label = isset($params['name']) ? $params['name'].' Template' : 'Default Template';
            $sourceModel = isset($params['template_list_model']) ? $params['template_list_model'] : null;
            $depends['fields']['*/*/'.$idEnabled] = [
                'id' => 'magesail_send/transactionals/'.$idEnabled,
                'value' => '1',
                '_elementType' => 'field',
                'dependPath' => [
                    0 => 'magesail_send',
                    1 => 'transactionals',
                    2 => $idEnabled,
                ],
            ];
        }

        return [
            'id' => $id,
            'translate' => 'label',
            'type' => 'select',
            'showInDefault' => '1',
            'showInWebsite' => '1',
            'showInStore' => '1',
            'sortOrder' => isset($params['sort_order']) ? $params['sort_order'] : '1',
            'label' => $label,
            'source_model' => $sourceModel,
            'depends' => $depends,
            '_elementType' => 'field',
            'path' => 'magesail_send/transactionals',
        ];
    }
}