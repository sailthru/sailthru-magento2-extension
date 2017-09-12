<?php

namespace Sailthru\MageSail\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Email\Model\Template as EmailTemplateModel;

/**
* 
*/
class Template extends EmailTemplateModel
{
    /**
     * To get template_code by template_id.
     * 
     * @param  string $id
     * 
     * @return mixed
     */
    public function getTemplateDataById($id)
    {
        $data = [];

        $collection = ObjectManager::getInstance()
            ->create('\Magento\Email\Model\ResourceModel\Template\CollectionFactory')
            ->create()
            ->addFieldToFilter('template_id', $id);


        foreach ($collection as $template) {
            if ($templateData = $template->getData()) {
                $data = $templateData;
                break;
            }
        }

        return empty($data) ? null : $data;
    }
}
