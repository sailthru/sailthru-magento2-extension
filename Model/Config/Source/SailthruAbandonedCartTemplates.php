<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

class SailthruAbandonedCartTemplates extends AbstractSource
{
    /** @inheritdoc */
    protected function getDisplayData()
    {
        $data = $this->apiHelper->getSailthruTemplates();
        $tpl_options = [];
        
        if (!isset($data["templates"]))
            return $tpl_options;

        foreach ($data["templates"] as $tpl) {
            $tpl_options[] = [
                'value' => $tpl['name'],
                'label' => $tpl['name'],
            ];
        }
        
        return $tpl_options;
    }
}
