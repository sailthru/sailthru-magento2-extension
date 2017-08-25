<?php

namespace Sailthru\MageSail\Plugin;

use \Magento\Config\Model\Config\Structure\Element\Group as OriginalGroup;
use Sailthru\MageSail\Model\Config\Template\Data as TemplateConfig;

class Group
{
    const REQUIRED_GROUP_ID = 'transactionals';

    /**
     * @var \Sailthru\MageSail\Model\Config\Template\Data
     **/
    protected $templateConfig;

    /**
     * Group constructor.
     * 
     * @param TemplateConfig $templateConfig
     */
    public function __construct(
        TemplateConfig $templateConfig
    )
    {
        $this->templateConfig = $templateConfig;
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
        if($data['id'] == self::REQUIRED_GROUP_ID) {
            $dynamicFields = self::getDynamicConfigFields();
            if(!empty($dynamicFields)) {
                $data['children'] += $dynamicFields;
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
            foreach ($templateList as $template) {
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
     * @param array  $params
     * @param string $type
     *
     * @return  array
     */
    protected function addField($params, $type)
    {
        # TODO: optimize adding of dependency fields
        
        if ('enabled' == $type) {
            $id = isset($params['id']) ? $params['id'].'_enabled' : '';
            $label = isset($params['name']) ? 'Override Magento '.$params['name'] : 'Default label';
            $sourceModel = isset($params['enabled_model']) ? $params['enabled_model'] : null;
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
        } else {
            $id = isset($params['id']) ? $params['id'] : '';
            $idEnabled = isset($params['id']) ? $params['id'].'_enabled' : '';
            $label = isset($params['name']) ? $params['name'].' Template' : 'Default Template';
            $sourceModel = isset($params['template_list_model']) ? $params['template_list_model'] : null;
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
                    '*/*/'.$idEnabled => [
                        'id' => 'magesail_send/transactionals/'.$idEnabled,
                        'value' => '1',
                        '_elementType' => 'field',
                        'dependPath' => [
                            0 => 'magesail_send',
                            1 => 'transactionals',
                            2 => $idEnabled,
                        ],
                    ],
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