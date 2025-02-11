<?php

namespace app\controllers;

use app\models\Notificaciones;
use app\models\NotificacionesSearch;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;


use app\controllers\base\NotificacionesController as BaseNotificacionesController;

/**
 * This is the class for controller "NotificacionesController".
 */
class NotificacionesController extends BaseNotificacionesController
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
                            'actions' => ['index', 'view', 'create', 'update', 'delete', 'ver-detalle', 'set-leidas', 'delete-leidos', 'delete-todas'],
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



        $searchModel = Yii::createObject(NotificacionesSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get(),Yii::$app->user->identity->id);

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
        throw new HttpException(403, "Forbidden");

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

        throw new HttpException(403, "Forbidden");
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
        throw new HttpException(403, "Forbidden");
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
        throw new HttpException(403, "Forbidden");
    }


    public function actionSetLeidas()
    {
        Yii::$app->response->format = Yii\web\Response::FORMAT_JSON;


        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');




            $model = Notificaciones::findOne($id);

            if (!$model) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $model->leida = $estado;

            if ($model->save()) {
                return [
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'id' => $id,
                    'estado' => $estado,
                ];

            } else {

                return [
                    'success' => false,
                    'message' => 'Error ' . implode(",", $model->getErrorSummary(true)),
                    'id' => $id,
                    'estado' => $estado,
                ];

            }
        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }


    public function actionVerDetalle()
    {


        if (isset($_POST['expandRowKey'])) {


            $model = $this->findModel($_POST['expandRowKey']);

            return Yii::$app->controller->renderPartial('_verMensaje', [
                'model' => $model->msg
            ]);

        } else {
            return '<div class="alert alert-danger">No se encontraron pagos</div>';
        }
    }


    /**
     * Deletes an existing model.
     * If deletion is successful, 
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDeleteLeidos()
    {

        try {

            Notificaciones::deleteAll(['id_user' => Yii::$app->user->identity->id, 'leida' => 1]);

            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
        }

        return $this->redirect(['index']);
    }


    /**
     * Deletes an existing  model.
     * If deletion is successful, 
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDeleteTodas()
    {

        try {

            Notificaciones::deleteAll(['id_user' => Yii::$app->user->identity->id]);

            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
        }

        return $this->redirect(['index']);
    }

}
