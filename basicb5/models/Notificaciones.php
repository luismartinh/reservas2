<?php

namespace app\models;

use \app\models\base\Notificaciones as BaseNotificaciones;
use Yii;

/**
 * This is the model class for table "notificaciones".
 */
class Notificaciones extends BaseNotificaciones
{


    public static function getCantNoLeidas($user)
    {

        return Notificaciones::find()->where(['id_user' => $user->id, 'leida' => 0])->count();
    }


    private static function getLastchangeAuditoria($tabla){

        if(!Auditoria::hayQueAuditar($tabla)){
            return "<div class=\"alert alert-danger\">Para ver los cambios debe activar la auditoria de $tabla </div>";
        }
        $last=Auditoria::find()->where(['tabla'=>$tabla])->orderBy(['id'=>SORT_DESC])->one();
        if(!$last){
            return "<div class=\"alert alert-danger\">No se registro el cambio en auditoria de $tabla </div>";
        }

        return '<pre style="padding:10px; border-radius:5px;">' . 
            json_encode($last->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
            '</pre>';
    }

    public static function NotificarANivel($nivel, $tabla, $mensaje,$showChange=true)
    {

        $grupos = GrupoAcceso::find()->where(['nivel' => $nivel])->all();

        $users_ids = [];
        foreach ((array) $grupos as $grupo) {

            $users = $grupo->getUsuarios()->select(['id'])->distinct()->asArray()->all();

            $id_array = array_column($users, 'id');

            $users_ids = array_unique(array_merge($users_ids, $id_array));

        }

        $usrs = Usuario::find()->where(['nivel' => $nivel])->select(['id'])->distinct()->asArray()->all();
        $id_array = array_column($usrs, 'id');
        $users_ids = array_unique(array_merge($users_ids, $id_array));


        if($showChange){

            $change=self::getLastchangeAuditoria($tabla);

            $msg="<p>$mensaje</p>$change";

        }else{
            $msg="<p>$mensaje</p>";
        }

        if (count($users_ids) > 0) {
            self::Nueva($users_ids, $tabla, $msg);
        }

    }

    public static function Nueva($users_ids, $tabla, $texto)
    {

        if (!Notificaciones::hayQueNotificar($tabla))
            return;

        $msg = new NotifMensajes();

        $msg->msg = $texto;

        if (!$msg->save()) {
            Yii::error("ERROR: Notificaciones.hayQueNotificar() msg->save()". implode(",", $msg->getErrorSummary(true)));
            throw new \Exception("Error notif.msg save" . implode(",", $msg->getErrorSummary(true)));
            
        }


        foreach ((array) $users_ids as $user_id) {

            $notif = new Notificaciones();

            $notif->id_user = $user_id;
            $notif->id_msg = $msg->id;
            $notif->leida = 0;
            $notif->tabla=$tabla;

            if (!$notif->save()) {
                Yii::error("ERROR: Notificaciones.hayQueNotificar() notif->save()". implode(",", $notif->getErrorSummary(true)));
                throw new \Exception("Error notif save " . implode(",", $notif->getErrorSummary(true)));
            }


        }
    }


    public static function hayQueNotificar($tabla)
    {
        $a_t = NotifTablas::find()->where(['tabla' => $tabla])->one();

        if (!$a_t)
            return false;


        return $a_t->enabled === 1 ? true : false;
    }

}
