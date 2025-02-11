<?php
namespace app\config;



class Niveles
{
    public const SYSADMIN = 0;
    public const ADMIN = 1;
    public const MANAGER = 2;  
    public const OPERATOR = 3;  
    public const OP_LIMITED = 4;  
    public const WEBUSER = 5;  


    public static function getNiveles()
    {
        return [
            self::SYSADMIN => 'SUPER ADMIN',
            self::ADMIN => 'ADMIN',
            self::MANAGER => 'GERENTE',
            self::OPERATOR => 'OPERADOR',
            self::OP_LIMITED => 'OP.LIMITADO',
            self::WEBUSER => 'USUARIO WEB',
        ];
    }

    public static function getNivelesDesde($from)
    {

        $niveles = self::getNiveles();
        //return array_slice($niveles, $from);

        return array_slice($niveles, $from, null, true); 
    }

}
