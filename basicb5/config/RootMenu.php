<?php
namespace app\config;



class RootMenu
{
    public const CONFIG = 0;
    public const ADMIN = 1;
    public const OPERATOR = 2;  
    public const REPORT = 3;  
    public const OTHER = 4;  
    public const WEBUSER = 5;  


    public static function getROOT()
    {

        $root[self::CONFIG] = ["label"=>"Config","icon"=>"<i class=\"bi bi-gear-fill\"></i>"];
        $root[self::ADMIN] = ["label"=>"Administrar","icon"=>"<i class=\"bi bi-toggles\"></i>"];
        $root[self::OPERATOR] = ["label"=>"Operatoria","icon"=>"<i class=\"bi bi-calculator\"></i>"];
        $root[self::REPORT] = ["label"=>"Reportes","icon"=>"<i class=\"bi bi-table\"></i>"];
        $root[self::OTHER] = ["label"=>"Otros","icon"=>"<i class=\"bi bi-collection\"></i>"];
        $root[self::WEBUSER] = ["label"=>"Usuario","icon"=>"<i class=\"bi bi-person-fill\"></i>"];

        return $root;
    }
}
