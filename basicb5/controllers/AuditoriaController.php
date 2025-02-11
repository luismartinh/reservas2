<?php

namespace app\controllers;

use app\config\RootMenu;
use app\models\Auditoria;
use app\models\AuditoriaSearch;
use app\models\Identificador;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use Yii;



use app\controllers\base\AuditoriaController as BaseAuditoriaController;

/**
 * This is the class for controller "AuditoriaController".
 */
class AuditoriaController extends BaseAuditoriaController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'create', 'update', 'delete','ver-detalle'],
                            'roles' => ['@'],
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * Lists all Auditoria models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $menu = new \app\models\Menu();
        $menu->descr = "Ver Auditoria cambios";
        $menu->label = "Auditoria";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Auditoria";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Ver Auditoria cambios",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(AuditoriaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single Auditoria model.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string
     */
    public function actionView($id)
    {
        throw new HttpException(403,"Forbidden");

    }

    /**
     * Creates a new Auditoria model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @throws yii\base\InvalidConfigException
     * @return string|Response
     */
    public function actionCreate()
    {

        throw new HttpException(403,"Forbidden");
    }

    /**
     * Updates an existing Auditoria model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        throw new HttpException(403,"Forbidden");
    }

    /**
     * Deletes an existing Auditoria model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDelete($id)
    {
        throw new HttpException(403,"Forbidden");
    }


    public function actionVerDetalle()
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Ver Auditoria cambios",
            null
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        if (isset($_POST['expandRowKey'])) {


            $model = $this->findModel($_POST['expandRowKey']);
            

            return Yii::$app->controller->renderPartial('_verDetalle', [
                'model' => $model,
            ]);

        } else {
            return '<div class="alert alert-danger">No se encontraron pagos</div>';
        }
    }


}
