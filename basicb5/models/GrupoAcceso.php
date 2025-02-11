<?php

namespace app\models;

use \app\models\base\GrupoAcceso as BaseGrupoAcceso;

/**
 * This is the model class for table "grupo_acceso".
 */
class GrupoAcceso extends BaseGrupoAcceso
{

    public function setUsuario($id_usuario, $add_remove = true)
    {

        $usuario = Usuario::findOne($id_usuario);

        if (!$usuario) {
            return false;
        }

        $userGrupo = GrupoAccesoUsuario::find()->where(['id_usuario' => $id_usuario, 'id_grupo_acceso' => $this->id])->one();

        if ($add_remove) {

            if (!$userGrupo) {
                $userGrupo = new GrupoAccesoUsuario();
                $userGrupo->id_grupo_acceso = $this->id;
                $userGrupo->id_usuario = $id_usuario;
                $userGrupo->save();
            }
        } else {
            if ($userGrupo) {
                $userGrupo->delete();
            }
        }

        return true;
    }



    public function setAcceso($id_acceso, $add_remove = true)
    {

        $acceso = Acceso::findOne($id_acceso);

        if (!$acceso) {
            return false;
        }

        $gaAcceso = GrupoAccesoAcceso::find()->where(['id_grupo_acceso' => $this->id, 'id_acceso' => $id_acceso])->one();

        if ($add_remove) {

            if (!$gaAcceso) {
                $gaAcceso = new GrupoAccesoAcceso();
                $gaAcceso->id_acceso = $id_acceso;
                $gaAcceso->id_grupo_acceso = $this->id;
                $gaAcceso->save();
            }
        } else {
            if ($gaAcceso) {
                $gaAcceso->delete();
            }
        }

        return true;
    }

    public function tienePermiso($check_acceso)
    {

        $accesos = $this->accesos;

        foreach ($accesos as $acceso) {
            if ($acceso->acceso == $check_acceso->acceso) {
                return true;
            }
        }

        return false;
    }


}
