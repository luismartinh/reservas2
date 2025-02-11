<?php

namespace app\controllers;

use app\config\RootMenu;
use app\models\Identificador;
use app\models\NotifTablasSearch;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;


use app\controllers\base\NotifTablasController as BaseNotifTablasController;

/**
 * This is the class for controller "NotifTablasController".
 */
class NotifTablasController extends BaseNotifTablasController
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
                            'actions' => ['index', 'view', 'create', 'update', 'delete','activate'],
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
        $menu->descr = "Administrar notificaciones";
        $menu->label = "Auditoria";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Notificaciones";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Administrar notificaciones",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(NotifTablasSearch::class);
        $dataProvider = $searchModel->search($this->request->get());

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


    public function actionActivate()
    {
        Yii::$app->response->format = Yii\web\Response::FORMAT_JSON;

        
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Administrar notificaciones",
            null);

        if (!$permiso['auth']) {
            return ['success' => false, 'message' => $permiso["msg"]];
        }

        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');




            $model = NotifTablasSearch::findOne($id);

            if (!$model) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $model->enabled=$estado;

            if($model->save()){
                return [
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'id' => $id,
                    'estado' => $estado,
                ];
    
            }else{

                return [
                    'success' => false,
                    'message' => 'Error '. implode(",",$model->getErrorSummary(true) ) ,
                    'id' => $id,
                    'estado' => $estado,
                ];
    
            }
        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }



}
