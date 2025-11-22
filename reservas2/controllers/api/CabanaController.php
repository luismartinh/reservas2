<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "CabanaController".
 */

use app\models\Cabana;
use yii\rest\ActiveController;

class CabanaController extends ActiveController
{
    public $modelClass = Cabana::class;
}
