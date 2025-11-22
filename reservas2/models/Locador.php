<?php

namespace app\models;

use \app\models\base\Locador as BaseLocador;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "locadores".
 */
class Locador extends BaseLocador
{

    public static function getAllLocadoresDropdown()
    {
        $query = self::find()
            ->select(['id', "CONCAT(denominacion, ' - ', documento, ' - ', email) as text"])
            ->asArray()
            ->all();

        return ArrayHelper::map($query, 'id', 'text');

    }

}
