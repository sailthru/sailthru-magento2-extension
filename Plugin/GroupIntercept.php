<?php

namespace Sailthru\MageSail\Plugin;

use \Magento\Framework\Exception\MailException;
use \Magento\Config\Model\Config\Structure\Element\Group as OriginalGroup;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\Settings;

class GroupIntercept
{
    /** Prefix for name of new templates. */
    const MAGENTO_TEMPLATE_NAME_PREFIX = 'magento_';

    /** Name of the `Generic` template. */
    const MAGENTO_GENERIC_TEMPLATE = 'Magento Generic';

    /** Group id for additional fields. */
    const GROUP_WITH_CONFIG_FIELDS = 'transactionals';

    /** List of groups in module. */
    const REQUIRED_GROUP_IDS = [
        'intercept',
        'tags',
        'lists',
        'abandoned_cart',
        'transactionals',
    ];

    /** Renderer class. */
    const RENDERER_CLASS = 'Sailthru\MageSail\Block\System\Config\Api\FieldRenderer';

    /** Source model for dynamic fields. */
    const FIELD_SOURCE_MODEL = 'Sailthru\MageSail\Model\Config\Source\SailthruTemplates';

    /** @var \Sailthru\MageSail\Model\Config\Template\Data */
    private $templateConfig;

    /** @var Sailthru\MageSail\Helper\Settings */
    private $sailthruSettings;

    /** @var Sailthru\MageSail\Helper\Api */
    private $apiHelper;

    /**
     * Group constructor.
     * 
     * @param TemplateConfig $templateConfig
     * @param Settings       $sailthruSettings
     * @param Api            $apiHelper
     */
    public function __construct(
        TemplateConfig $templateConfig,
        Settings $sailthruSettings,
        Api $apiHelper
    )
    {
        $this->templateConfig = $templateConfig;
        $this->sailthruSettings = $sailthruSettings;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Set flyweight data.
     *
     * @param  OriginalGroup $subject
     * @param  callable      $proceed
     * @param  array         $data
     * @param                $scope
     * 
     * @return mixed
     */
    public function aroundSetData(
        OriginalGroup $subject,
        callable $proceed,
        array $data,
        $scope
    ) {
        if (!in_array($data['id'], self::REQUIRED_GROUP_IDS))
            return $proceed($data, $scope);

        $data['children'] = $this->addRendered($data['children'] ?? []);
        if (self::GROUP_WITH_CONFIG_FIELDS == $data['id']) {
            $dynamicFields = $this->getDynamicConfigFields();
            if (!empty($dynamicFields)) {
                $data['children'] += $dynamicFields;
            }
        }

        return $proceed($data, $scope);
    }

    /**
     * To add custom renderer.
     * 
     * @param   array $fields
     *
     * @return  array
     */
    private function addRendered($fields)
    {
        foreach ($fields as &$field) {
            $field['frontend_model'] = self::RENDERER_CLASS;
        }

        return $fields;
    }

    /**
     * To get field list.
     * 
     * @return array $fields
     */
    private function getDynamicConfigFields()
    {
        $fields = [];
        $templateList = $this->templateConfig->get('templates');

        if (!$templateList)
            return $fields;

        $sailthruTemplates = array_column($this->apiHelper->getSailthruTemplates()['templates'], 'name') ?? [];
        # Create the `Magento Generic` template if doesn't exists.
        $sender = $this->sailthruSettings->getSender();
        if ($sender && !in_array(self::MAGENTO_GENERIC_TEMPLATE, $sailthruTemplates)) {
            $this->apiHelper->saveTemplate(self::MAGENTO_GENERIC_TEMPLATE, $sender);
        }
        
        foreach ($templateList as $template) {
            # Create the `specific` template if doesn't exists.
            if ($sender && !in_array(self::MAGENTO_TEMPLATE_NAME_PREFIX . $template['id'], $sailthruTemplates)) {
                $this->apiHelper->saveTemplate(self::MAGENTO_TEMPLATE_NAME_PREFIX . $template['id'], $sender);
            }

            $tmpListField = self::addField($template, 'template_list');

            $fields[$tmpListField['id']] = $tmpListField;
        }

        return $fields;
    }

    /**
     * To get field configuration.
     * 
     * @param   array  $params
     * @param   string $type
     *
     * @return  array
     */
    private function addField($params, $type)
    {
        $id = $params['id'] ?? '';
        $idEnabled = $params['id'] . '_enabled' ?? '';
        $label = $params['name'] . ' Template' ?? 'Default Template';
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

        return [
            'id' => $id,
            'translate' => 'label',
            'type' => 'select',
            'showInDefault' => '1',
            'showInWebsite' => '1',
            'showInStore' => '1',
            'sortOrder' => $params['sort_order'] ?? '1',
            'label' => $label,
            'source_model' => self::FIELD_SOURCE_MODEL,
            'frontend_model' => self::RENDERER_CLASS,
            'depends' => $depends,
            '_elementType' => 'field',
            'path' => 'magesail_send/transactionals',
        ];
    }
}
