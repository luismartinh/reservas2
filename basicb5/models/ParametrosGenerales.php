<?php

namespace app\models;

use \app\models\base\ParametrosGenerales as BaseParametrosGenerales;
use Yii;

/**
 * This is the model class for table "parametros_generales".
 */
class ParametrosGenerales extends BaseParametrosGenerales
{


    public static function getParametro($clave){
        return ParametrosGenerales::find()->where(['clave'=>$clave])->one();
    }

    public static function setParametro($clave,$valor,$descr, $updateIfExist=true){

        $par=ParametrosGenerales::find()->where(['clave'=>$clave])->one();
        if(!$par){
            $par=new ParametrosGenerales();
            $par->clave=$clave;
            $par->descr=$descr;
            $par->valor=$valor;
        }else{
            if(!$updateIfExist) return;
            $par->valor=$valor;
            if($descr) $par->descr=$descr;
        }

        if (!$par->save()) {
            Yii::error("ERROR: ParametrosGenerales.setParametro() par->save()". implode(",", $par->getErrorSummary(true)));
            throw new \Exception("Error ParametrosGenerales.setParametro() par->save() " . implode(",", $par->getErrorSummary(true)));
        }

    } 


    public static function updateParametro($clave,$valor){

        $par=ParametrosGenerales::find()->where(['clave'=>$clave])->one();
        if(!$par){
            return false;
        }else{
            $par->valor=$valor;
        }

        if (!$par->save()) {
            Yii::error("ERROR: ParametrosGenerales.setParametro() par->save()". implode(",", $par->getErrorSummary(true)));
            throw new \Exception("Error ParametrosGenerales.updateParametro() par->save() " . implode(",", $par->getErrorSummary(true)));
        }

        return true;
    } 

}
