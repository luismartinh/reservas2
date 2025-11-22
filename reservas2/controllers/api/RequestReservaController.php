<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "RequestReservaController".
 */

use app\models\RequestReserva;
use yii\rest\ActiveController;

class RequestReservaController extends ActiveController
{
    public $modelClass = RequestReserva::class;
}
