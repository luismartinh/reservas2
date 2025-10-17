<?php

namespace app\models;

use \app\models\base\Usuario as BaseUsuario;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "usuario".
 */
class Usuario extends BaseUsuario
{
    public $id_punto_venta_default;


    public function rules()
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [['id_punto_venta_default'], 'integer'],
                [['id_punto_venta_default'], 'safe'],
                [
                    ['id_punto_venta_default'],
                    'exist',
                    'skipOnError' => true,
                    'targetClass' => \app\models\PuntoVenta::class,
                    'targetAttribute' => ['id_punto_venta_default' => 'id']
                ]
            ]
        );
    }

    public function setGrupoAcceso($id_grupoAcceso, $add_remove = true)
    {

        $grupo = GrupoAcceso::findOne($id_grupoAcceso);

        if (!$grupo) {
            return false;
        }

        $userGrupo = GrupoAccesoUsuario::find()->where(['id_usuario' => $this->id, 'id_grupo_acceso' => $id_grupoAcceso])->one();

        if ($add_remove) {

            if (!$userGrupo) {
                $userGrupo = new GrupoAccesoUsuario();
                $userGrupo->id_grupo_acceso = $id_grupoAcceso;
                $userGrupo->id_usuario = $this->id;
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

        $userAcceso = UsuarioAcceso::find()->where(['id_usuario' => $this->id, 'id_accesos' => $id_acceso])->one();

        if ($add_remove) {

            if (!$userAcceso) {
                $userAcceso = new UsuarioAcceso();
                $userAcceso->id_accesos = $id_acceso;
                $userAcceso->id_usuario = $this->id;
                $userAcceso->save();
            }
        } else {
            if ($userAcceso) {
                $userAcceso->delete();
            }
        }

        return true;
    }


    public function accesosDisponibles()
    {
        //return $this->hasMany(Acceso::class, ['id' => 'id_accesos'])->viaTable('usuarios_accesos', ['id_usuario' => 'id']);

        $grupo = $this->getGrupoAccesos()->orderBy(['nivel' => SORT_ASC])->one();

        if (!$grupo) {
            return $this->getAccesos();//->where('0=1');
        }

        return $grupo->getAccesos();
    }

}
