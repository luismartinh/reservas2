<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "ParametrosGeneralesController".
 */

use app\models\ParametrosGenerales;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use Yii;

class ParametrosGeneralesController extends ActiveController
{
    public $modelClass = ParametrosGenerales::class;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can($this->module->id . '_' . $this->id . '_' . $action->id, ['route' => true]);
                            }
                        ]
                    ]
                ]
            ]
        );
    }
}
