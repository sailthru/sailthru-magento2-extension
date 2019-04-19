<?php

namespace Sailthru\MageSail\Model\Config\Source;

class ValidatedLOEnableDisable extends AbstractSource
{
    protected function getDisplayData()
    {
        return [
            ['value' => 2, 'label' => __('Use Reminder Template')],
            ['value' => 1, 'label' => __('Use Lifecycle Optimizer (best practice)')],
            ['value' => 0, 'label' => __('Disable')]
        ];
    }
}