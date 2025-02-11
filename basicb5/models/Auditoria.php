<?php

namespace app\models;

use \app\models\base\Auditoria as BaseAuditoria;

/**
 * This is the model class for table "auditorias".
 */
class Auditoria extends BaseAuditoria
{


    public static function getDropdownOptions(){

        $actions = self::find()->select(['action'])->distinct()->asArray()->all();

        
        $actions = array_column($actions, 'action');

        $ret=[];

        foreach((array)$actions as $action){
            $ret[$action]=$action;
        }

        return $ret;

    }


    public static function hayQueAuditar($tabla){
        $a_t =AuditoriaTabla::find()->where(['tabla'=>$tabla])->one();

        if(!$a_t) return false;


        return $a_t->enabled=="1"?true:false;
    }
}
