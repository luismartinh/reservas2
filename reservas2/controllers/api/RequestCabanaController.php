<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "RequestCabanaController".
 */

use app\models\RequestCabana;
use yii\rest\ActiveController;

class RequestCabanaController extends ActiveController
{
    public $modelClass = RequestCabana::class;
}
