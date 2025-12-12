<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "LocadorController".
 */

use app\models\Locador;
use yii\rest\ActiveController;

class LocadorController extends ActiveController
{
    public $modelClass = Locador::class;
}
