<?php

namespace app\controllers;

use app\config\RootMenu;
use app\models\base\CabanaTarifa;
use app\models\Cabana;
use app\models\CabanaSearch;
use app\models\Identificador;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;


use app\controllers\base\CabanaController as BaseCabanaController;

/**
 * This is the class for controller "CabanaController".
 */
class CabanaController extends BaseCabanaController
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
                    'only' => [
                        'index',
                        'view',
                        'create',
                        'update',
                        'delete',
                        'vincular-cabanas-tarifas',
                        'eliminar-vinculacion'
                    ],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => [
                                'index',
                                'view',
                                'create',
                                'update',
                                'delete',
                                'vincular-cabanas-tarifas',
                                'eliminar-vinculacion'
                            ],
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
     * Lists all Cabana models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Cabañas";
        $menu->label = "Cabañas";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Reservas/Cabañas";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Cabañas",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $searchModel = Yii::createObject(CabanaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


    /**
     * Displays a single Cabana model.
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
            "Ver Cabaña",
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
     * Creates a new Cabana model.
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
            "Crear Cabaña",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = Yii::createObject(Cabana::class);

        $paleta = Cabana::getPaletaTraducida();

        $usados = Cabana::coloresUsados();
        // Filtrar por clave (hex)
        $coloresDisponibles = array_diff_key($paleta, array_flip($usados));

        $numerosDisponibles = Cabana::getNumerosDisponibles($model);



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
        return $this->render('create', [
            'model' => $model,
            'coloresDisponibles' => $coloresDisponibles,
            'numerosDisponibles' => $numerosDisponibles,
        ]);
    }



    /**
     * Updates an existing Cabana model.
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
            "Modificar Cabaña",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = $this->findModel($id);

        $paleta = Cabana::getPaletaTraducida();

        $usados = Cabana::coloresUsados();
        $usados = array_diff($usados, [$model->color_cabana]); // permitir elegir el color actual
        // Filtrar por clave (hex)
        $coloresDisponibles = array_diff_key($paleta, array_flip($usados));
        $numerosDisponibles = Cabana::getNumerosDisponibles($model);


        if ($model->load($this->request->post())) {

            if ($model->save()) {
                Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'coloresDisponibles' => $coloresDisponibles,
            'numerosDisponibles' => $numerosDisponibles,
        ]);
    }



    /**
     * Deletes an existing Cabana model.
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
            "Eliminar Cabaña",
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



    public function actionVincularCabanasTarifas($id_cabana)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Cabaña",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $cabana = Cabana::findOne($id_cabana);
        if (!$cabana) {
            throw new NotFoundHttpException('Cabaña no encontrada.');
        }

        // POST: vincular múltiples
        if (Yii::$app->request->isPost) {
            $ids = (array) Yii::$app->request->post('tarifa_ids', []);
            $ok = 0;
            $errors = [];

            foreach ($ids as $idTarifa) {
                $idTarifa = (int) $idTarifa;

                // ya vinculada
                if (
                    \app\models\CabanaTarifa::find()->where([
                        'id_cabana' => $id_cabana,
                        'id_tarifa' => $idTarifa
                    ])->exists()
                ) {
                    continue;
                }

                $t = \app\models\Tarifa::findOne($idTarifa);
                if (!$t || !$t->inicio || !$t->fin) {
                    $errors[] = "Tarifa #$idTarifa inválida.";
                    continue;
                }

                /*
                // verificar solapamiento contra las ya asociadas
                $overlap = (new \yii\db\Query())
                    ->from(['ct' => 'cabanas_tarifas'])
                    ->innerJoin(['tx' => 'tarifas'], 'tx.id = ct.id_tarifa')
                    ->where(['ct.id_cabana' => $id_cabana])
                    ->andWhere(new \yii\db\Expression('NOT (:new_fin < tx.inicio OR :new_ini > tx.fin)'))
                    ->params([
                        ':new_ini' => $t->inicio,
                        ':new_fin' => $t->fin,
                    ])
                    ->exists();

                if ($overlap) {
                    $errors[] = "La tarifa '{$t->descr}' se superpone con otra ya asociada.";
                    continue;
                }
                */

                /*
                $res = CabanaTarifa::isTarifaRangeOverlap($id_cabana, $idTarifa);

                if ($res['status'] == 'error') {
                    $errors[] = $res['msg'];
                    continue;
                }

                if ($res['status'] == 'SI') {
                    $errors[] = "La tarifa '{$t->descr}' se superpone con otra ya asociada.";
                    continue;
                }
                */

                $ct = new \app\models\CabanaTarifa();
                $ct->id_cabana = $id_cabana;
                $ct->id_tarifa = $idTarifa;

                if ($ct->save())
                    $ok++;
                else
                    $errors[] = "No se pudo vincular '{$t->descr}'.";
                array_merge($errors, (array) $ct->getErrorSummary(true));
            }

            if ($ok > 0)
                Yii::$app->session->setFlash('success', "Se vincularon $ok tarifa(s).");
            if ($errors)
                Yii::$app->session->setFlash('error', implode('<br>', $errors));

            // Si NO es PJAX, redirigimos (flujo tradicional)
            if (!Yii::$app->request->isPjax) {
                return $this->redirect(['vincular-cabanas-tarifas', 'id_cabana' => $id_cabana]);
            }
            // Si es PJAX, seguimos para re-renderizar debajo
        }

        // DataProvider: ya vinculadas
        $query = \app\models\CabanaTarifa::find()
            ->where(['id_cabana' => $id_cabana])
            ->joinWith('tarifa t');

        $dataProvider = new \yii\data\ActiveDataProvider(['query' => $query]);

        /*
        // Selector: disponibles (no vinculadas + sin solape entre sí)
        $sub1 = \app\models\CabanaTarifa::find()->alias('ct1')
            ->where('ct1.id_tarifa = t.id')
            ->andWhere(['ct1.id_cabana' => $id_cabana]);

        $sub2 = (new \yii\db\Query())
            ->from(['ct2' => 'cabanas_tarifas'])
            ->innerJoin(['t2' => 'tarifas'], 't2.id = ct2.id_tarifa')
            ->where(['ct2.id_cabana' => $id_cabana])
            ->andWhere(new \yii\db\Expression('NOT (t.fin < t2.inicio OR t.inicio > t2.fin)'));

        $tarifasDisponibles = \app\models\Tarifa::find()->alias('t')
            ->where(['not exists', $sub1])
            ->andWhere(['not exists', $sub2])
            ->orderBy(['t.inicio' => SORT_ASC])
            ->all();
        */

        $tarifasDisponibles = \app\models\CabanaTarifa::getTarifasDisponibles($id_cabana);
        $listaTarifas = ArrayHelper::map(
            $tarifasDisponibles,
            'id',
            function ($m) {
                $ini = (new \DateTime($m->inicio))->format('d-m-Y');
                $fin = (new \DateTime($m->fin))->format('d-m-Y');
                $cd = $m->min_dias;
                $valor = $m->valor_dia;
                return "{$m->descr} ({$ini} → {$fin}) ({$cd}d / $ {$valor})";
            }
        );

        return $this->render('vincular-tarifas', [
            'cabana' => $cabana,
            'dataProvider' => $dataProvider,
            'listaTarifas' => $listaTarifas,
            'id_cabana' => $id_cabana,
        ]);
    }

    public function actionEliminarVinculacion($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Cabaña",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $ct = \app\models\CabanaTarifa::findOne($id);
        if (!$ct)
            throw new NotFoundHttpException('Vinculación no encontrada.');
        $id_cabana = $ct->id_cabana;
        $ct->delete();
        Yii::$app->session->setFlash('success', 'Vinculación eliminada.');

        // Soporta PJAX sin full reload
        if (Yii::$app->request->isPjax) {
            return $this->actionVincularCabanasTarifas($id_cabana);
        }
        return $this->redirect(['vincular-cabanas-tarifas', 'id_cabana' => $id_cabana]);
    }


}
