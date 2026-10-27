<?php

namespace app\models;

use \app\models\base\Locador as BaseLocador;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "locadores".
 * @property int|null $calificacion
 * @property int|null $id_localidad
 * @property int|null $id_provincia Selector, no columna de locadores.
 * @property Localidad|null $localidad
 * @property string|null $ubicacionDescripcion
 */
class Locador extends BaseLocador
{
    use UbicacionTrait;

    public function rules()
    {
        return array_merge(parent::rules(), $this->reglasUbicacion(), [
            [['calificacion'], 'default', 'value' => function ($model) {
                // Aplicar el default sólo si no se envió un valor; NULL sigue siendo válido.
                return $model->isNewRecord && !array_key_exists('calificacion', $model->getDirtyAttributes(['calificacion'])) ? 0 : null;
            }],
            [['calificacion'], 'integer', 'min' => 0, 'max' => 5],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'calificacion' => 'Calificación', 'id_provincia' => 'Provincia', 'id_localidad' => 'Localidad',
        ]);
    }

    public function getLocalidad() { return $this->hasOne(Localidad::class, ['id' => 'id_localidad']); }

    public function getUbicacionDescripcion()
    {
        $localidad = $this->localidad;
        return $localidad ? $localidad->descr . ' · ' . $localidad->provincia->descr : null;
    }

    public static function getAllLocadoresDropdown()
    {
        $query = self::find()
            ->select(['id', "CONCAT(denominacion, ' - ', documento, ' - ', email) as text"])
            ->asArray()
            ->all();

        return ArrayHelper::map($query, 'id', 'text');

    }

}
