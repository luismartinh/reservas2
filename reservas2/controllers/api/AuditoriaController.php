<?php

namespace app\controllers\api;

/**
 * This is the class for REST controller "AuditoriaController".
 */

use app\models\Auditoria;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use Yii;

class AuditoriaController extends ActiveController
{
    public $modelClass = Auditoria::class;

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
