<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class Provincia extends ActiveRecord
{
    public static function tableName() { return 'provincias'; }

    public function rules()
    {
        return [
            [['descr', 'slug'], 'trim'],
            [['descr', 'slug'], 'required'],
            [['descr'], 'string', 'max' => 150],
            [['slug'], 'string', 'max' => 45],
            [['descr'], 'unique'],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels() { return ['descr' => 'Provincia', 'slug' => 'Slug']; }

    public function getLocalidades()
    {
        return $this->hasMany(Localidad::class, ['id_provincia' => 'id'])->orderBy(['descr' => SORT_ASC]);
    }

    public static function opciones()
    {
        return ArrayHelper::map(static::find()->orderBy('descr')->asArray()->all(), 'id', 'descr');
    }
}
