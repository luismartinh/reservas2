<?php

namespace app\controllers;

use app\config\RootMenu;
use app\models\Identificador;
use app\models\Locador;
use app\models\LocadorSearch;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;


use app\controllers\base\LocadorController as BaseLocadorController;

/**
 * This is the class for controller "LocadorController".
 */
class LocadorController extends BaseLocadorController
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
                    'only' => ['index', 'view', 'create', 'update', 'delete'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'create', 'update', 'delete'],
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                return true;
                            }

                        ],
                    ]
                ]
            ]
        );
    }


    /**
     * Lists all Locador models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Pasajeros";
        $menu->label = "Pasajeros";
        $menu->menu = (string) RootMenu::ADMIN;
        $menu->menu_path = "Reservas/Pasajeros";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Pasajeros",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $searchModel = Yii::createObject(LocadorSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


    /**
     * Displays a single Locador model.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionView($id)
    {

        $user = Yii::$app->user->identity;

        $permiso = Identificador::autorizar(
            $user,
            Yii::$app->controller->id . '/view',
            "Ver Pasajero",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = $this->findModel($id);


        return $this->render('view', [
            'model' => $model,
            'request_get' => $this->request->get(),
        ]);

    }



    /**
     * Creates a new Locador model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @throws yii\base\InvalidConfigException
     * @return string|Response
     */
    public function actionCreate()
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/create',
            "Crear Pasajero",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = Yii::createObject(Locador::class);

        try {

            if ($model->load($this->request->post())) {

                if ($model->save()) {
                    Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
            if (!Yii::$app->request->isPost) {
                $model->load($this->request->get());
            }
        } catch (\Exception $e) {
            $model->addError('_exception', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/create " . ($e->errorInfo[2] ?? $e->getMessage()));
        }
        return $this->render('create', ['model' => $model]);
    }



    /**
     * Updates an existing Locador model.
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
            "Modificar Pasajero",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = $this->findModel($id);


        if ($model->load($this->request->post())) {

            if ($model->save()) {
                Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }



    /**
     * Deletes an existing Locador model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDelete($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/delete',
            "Eliminar Pasajero",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $modelOri = $this->findModel($id);



        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete " . ($e->errorInfo[2] ?? $e->getMessage()));
        }

        return $this->redirect(['index']);
    }

}
