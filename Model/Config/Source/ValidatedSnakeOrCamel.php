<?php

namespace Sailthru\MageSail\Model\Config\Source;

class ValidatedSnakeOrCamel extends AbstractSource
{
    protected function getDisplayData()
    {
        return [
            ['value' => 'camel', 'label' => __('camelCase')],
            ['value' => 'snake', 'label' => __('snake_case')]
        ];
    }
}