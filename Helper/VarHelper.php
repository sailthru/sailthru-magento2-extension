<?php

namespace Sailthru\MageSail\Helper;

class VarHelper
{
    private $vars = [
        'firstname' => ['first', 'name'],
        'middlename' => ['middle', 'name'],
        'lastname' => ['last', 'name'],
    ];
    private function formatCamelCaseVars($v)
    {
        return array_shift($v) . implode(array_map("ucfirst", $v));
    }
    private function formatSnakeCaseVars($v)
    {
        return implode("_", $v);
    }
    public function getVarKeys($case)
    {
        $ret = [];
        foreach($this->vars as $k => $v)
        {
            $ret[$k] = $case == "snake"
                ? $this->formatSnakeCaseVars($v)
                : $this -> formatCamelCaseVars($v);
        }
        return $ret;
    }
}