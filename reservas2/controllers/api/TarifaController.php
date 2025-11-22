<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "TarifaController".
 */

use app\models\Tarifa;
use yii\rest\ActiveController;

class TarifaController extends ActiveController
{
    public $modelClass = Tarifa::class;
}
