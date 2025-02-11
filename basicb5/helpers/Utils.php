<?php

namespace app\helpers;

class Utils
{
    public static function DMY2SQLdate($dateDMY, $sep = "/", $hr = "00:00:00")
    {

        $tk = explode($sep, (string) $dateDMY);
        if ($tk === false)
            return '';
        if (count($tk) != 3)
            return '';

        if ($hr)
            return "$tk[2]-$tk[1]-$tk[0] $hr";
        return "$tk[2]-$tk[1]-$tk[0]";
    }





}