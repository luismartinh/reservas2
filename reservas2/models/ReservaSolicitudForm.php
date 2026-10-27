<?php
namespace app\models;

/** Mantiene el contrato del formulario existente y comparte validación de ubicación. */
class ReservaSolicitudForm extends \yii\base\DynamicModel
{
    use UbicacionTrait;

    public function rules() { return $this->reglasUbicacion(); }

    public function formName() { return 'DynamicModel'; }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id_provincia' => 'Provincia', 'id_localidad' => 'Localidad',
        ]);
    }
}
