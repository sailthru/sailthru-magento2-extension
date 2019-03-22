<?php

namespace Sailthru\MageSail\Helper;

class VarHelper
{
    private $vars = [
        'firstname' => ['first', 'name'],
        'middlename' => ['middle', 'name'],
        'lastname' => ['last', 'name'],
    ];
    private function formatCamelCaseVars()
    {
        $ret = [];
        foreach($this->vars as $k => $v)
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
    private function formatSnakeCaseVars()
    {
        $ret = [];
        foreach($this->vars as $k => $v)
        {
            $ret[$k] = implode("_", $v);
        }
        return $ret;
    }
    public function getVarKeys($case)
    {
        if ($case == "snake")
        {
            return $this->formatSnakeCaseVars();
        }
        else
        {
            return $this->formatCamelCaseVars();
        }
    }
}