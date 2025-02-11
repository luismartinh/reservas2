<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "GrupoAccesoController".
 */

use app\models\GrupoAcceso;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use Yii;

class GrupoAccesoController extends ActiveController
{
    public $modelClass = GrupoAcceso::class;

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
