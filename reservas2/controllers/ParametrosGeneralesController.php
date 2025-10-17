<?php

namespace app\controllers;

use app\config\Niveles;
use app\config\RootMenu;
use app\models\Identificador;
use app\models\Notificaciones;
use app\models\ParametrosGenerales;
use app\models\ParametrosGeneralesSearch;
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


use app\controllers\base\ParametrosGeneralesController as BaseParametrosGeneralesController;

/**
 * This is the class for controller "ParametrosGeneralesController".
 */
class ParametrosGeneralesController extends BaseParametrosGeneralesController
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
                            'actions' => ['index', 'view', 'create', 'update', 'delete', 'ver-detalle','prueba'],
                            'roles' => ['@'],
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * Lists all ParametrosGenerales models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar parametros generales";
        $menu->label = "Usuarios";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Parametros";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar parametros generales",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(ParametrosGeneralesSearch::class);
        $dataProvider = $searchModel->search($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single ParametrosGenerales model.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string
     */
    public function actionView($id)
    {
        throw new HttpException(403, "Forbidden");

    }

    /**
     * Creates a new ParametrosGenerales model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @throws yii\base\InvalidConfigException
     * @return string|Response
     */
    public function actionCreate()
    {

        throw new HttpException(403, "Forbidden");
    }

    /**
     * Updates an existing ParametrosGenerales model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionUpdate($id)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar parametros",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = $this->findModel($id);
        if ($model->load($this->request->post())) {

            $model->valor = json_decode((string) $model->valor);
            if ($model->save()) {
                Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'parametros_generales', "Se modifico el parametro {$model->descr}");
                Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                return $this->redirect(['index']);
            }

        }
        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing ParametrosGenerales model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDelete($id)
    {

        throw new HttpException(403, "Forbidden");
    }


    public function actionVerDetalle()
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Administrar parametros generales",
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


    public function actionPrueba(){

        /*
        $var=[
            "attr1"=>"valor atrr 1",
            "attr2"=>"valor atrr 2",
            "attr3"=>[
                "atr31"=>"valor atrr 31",
                "atr32"=>"valor atrr 32",
                ]
            ];

        ParametrosGenerales::setParametro("CLAVE",$var,"prueba");    

        $par=print_r(ParametrosGenerales::getParametro("CLAVE")->valor);



        return  "<pre>$par</pre>";
        */

    }

}
