<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

class SailthruLists extends AbstractSource
{

    /** @inheritdoc */
    protected function getDisplayData()
    {
        $data = $this->clientManager->getClient()->getLists();
        $lists_options = [
            ['value'=> 0, 'label'=>' ']
        ];

        if (isset($data['lists'])) {
            $lists = $data["lists"];
        
            foreach ($lists as $list) {
                if ($list['type'] == 'normal') {
                    $lists_options[] = [
                        'value' => $list['name'],
                        'label' => __("{$list['name']} ({$list['email_count']} Emails)")
                    ];
                }
            }
        }

        return $lists_options;
    }
}
