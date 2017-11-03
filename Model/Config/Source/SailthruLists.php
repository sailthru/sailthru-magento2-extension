<?php
 
namespace Sailthru\MageSail\Model\Config\Source;

class SailthruLists extends AbstractSource
{

    /** @inheritdoc */
    protected function getDisplayData()
    {
        $data = $this->apiHelper->client->getLists();
        $lists_options = [
            ['value'=> 0, 'label'=>' ']
        ];

        if (isset($data['lists'])) {        
            foreach ($data["lists"] as $list) {
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
