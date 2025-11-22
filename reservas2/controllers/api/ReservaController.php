<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "ReservaController".
 */

use app\models\Reserva;
use yii\rest\ActiveController;

class ReservaController extends ActiveController
{
    public $modelClass = Reserva::class;
}
