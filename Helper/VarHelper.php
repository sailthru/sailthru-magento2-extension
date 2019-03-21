<?php

namespace Sailthru\MageSail\Helper;

class VarHelper

{
    private function formatCamelCaseVars($vars)
    {
        $ret = [];
        foreach($vars as $k => $v)
        {
            $str = $v[0];
            for ($i = 0; $i < count($v) - 1; $i++)
            {
                $str = $str . ucfirst($v[$i + 1]);
            }
            $ret[$k] = $str;
        }
        return $ret;
    }
    private function formatSnakeCaseVars($vars)
    {
        $ret = [];
        foreach($vars as $k => $v)
        {
            $str = $v[0];
            for ($i = 0; $i < count($v) - 1; $i++)
            {
                $str = $str . "_" . $v[$i + 1];
            }
            $ret[$k] = $str;
        }
        return $ret;
    }
    public function getNameKeys($case)
    {
        $vars = [
            'firstname' => ['first', 'name'],
            'middlename' => ['middle', 'name'],
            'lastname' => ['last', 'name'],
        ];
        if ($case == "snake")
        {
            return $this->formatSnakeCaseVars($vars);
        }
        else
        {
            return $this->formatCamelCaseVars($vars);
        }
    }
}