<?php

namespace Sailthru\MageSail\Model\Config\Source;

class ValidatedEnableDisable extends AbstractSource
{
    protected function getDisplayData()
    {
        return [
            ['value' => 1, 'label' => __('Enable')],
            ['value' => 0, 'label' => __('Disable')]
        ];
    }
}
