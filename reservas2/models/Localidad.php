<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class Localidad extends ActiveRecord
{
    public static function tableName() { return 'localidades'; }

    public function rules()
    {
        return [
            [['descr', 'slug'], 'trim'],
            [['id_provincia', 'descr', 'slug'], 'required'],
            [['id_provincia'], 'integer'],
            [['descr'], 'string', 'max' => 150],
            [['slug'], 'string', 'max' => 45],
            [['id_provincia'], 'exist', 'targetClass' => Provincia::class, 'targetAttribute' => 'id'],
            [['descr'], 'unique', 'targetAttribute' => ['id_provincia', 'descr']],
            [['slug'], 'unique', 'targetAttribute' => ['id_provincia', 'slug']],
        ];
    }

    public function attributeLabels()
    {
        return ['id_provincia' => 'Provincia', 'descr' => 'Localidad', 'slug' => 'Slug'];
    }

    public function getProvincia() { return $this->hasOne(Provincia::class, ['id' => 'id_provincia']); }
    public function getLocadores() { return $this->hasMany(Locador::class, ['id_localidad' => 'id']); }

    public static function opciones($provincia)
    {
        if (!$provincia || !is_scalar($provincia)) { return []; }
        return ArrayHelper::map(static::find()->where(['id_provincia' => $provincia])
            ->orderBy('descr')->asArray()->all(), 'id', 'descr');
    }
}
