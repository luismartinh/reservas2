<?php

namespace app\controllers;

use app\config\RootMenu;
use app\helpers\CalendarHelper;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\Identificador;
use app\models\Tarifa;
use app\models\TarifaSearch;
use yii\filters\AccessControl;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;


use app\controllers\base\TarifaController as BaseTarifaController;

/**
 * This is the class for controller "TarifaController".
 */
class TarifaController extends BaseTarifaController
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
                    'only' => ['index', 'view', 'create', 'update', 'delete', 'calendario', 'tarifas-por-cabana', 'editar-calendario-tarifa', 'crear-calendario-tarifa'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'create', 'update', 'delete', 'calendario', 'tarifas-por-cabana', 'editar-calendario-tarifa', 'crear-calendario-tarifa'],
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
     * Lists all Tarifa models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Tarifas";
        $menu->label = "Tarifas";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Reservas/Tarifas";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Tarifas",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $searchModel = Yii::createObject(TarifaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionCalendario()
    {
        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Tarifas";
        $menu->label = "Tarifas";
        $menu->menu = (string) RootMenu::ADMIN;
        $menu->menu_path = "Tarifas/Calendario";
        $menu->url = Yii::$app->controller->id . '/calendario';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar reservas",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $request = Yii::$app->request;
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $selectedCabanaId = (int) $request->get('id_cabana', 0);
        $selectedTarifaId = (int) $request->get('id_tarifa', 0);

        list($start1, $start2, $fromDate, $toDate) = CalendarHelper::buildTwoMonthRange($year, $month);

        $cabanas = Cabana::find()
            ->where(['activa' => 1])
            ->orderBy(['descr' => SORT_ASC])
            ->all();

        $cabanasDropdown = ArrayHelper::map($cabanas, 'id', 'descr');

        $tarifasDropdown = $selectedCabanaId > 0
            ? $this->buildTarifasDropdownByCabana($selectedCabanaId)
            : [];

        $tarifaItems = [];
        if ($selectedCabanaId > 0) {
            $tarifasCalendario = $this->findTarifasByCabanaAndRange($selectedCabanaId, $fromDate, $toDate, $selectedTarifaId);
            $tarifaItems = CalendarHelper::buildTarifaCalendarItems($tarifasCalendario);
        }

        return $this->render('calendario', [
            'start1' => $start1,
            'start2' => $start2,
            'tarifaItems' => $tarifaItems,
            'cabanasDropdown' => $cabanasDropdown,
            'tarifasDropdown' => $tarifasDropdown,
            'selectedCabanaId' => $selectedCabanaId > 0 ? $selectedCabanaId : null,
            'selectedTarifaId' => $selectedTarifaId > 0 ? $selectedTarifaId : null,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function actionTarifasPorCabana($id_cabana)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'results' => $this->buildTarifasOptionsByCabana((int) $id_cabana),
        ];
    }

    public function actionEditarCalendarioTarifa($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Tarifa",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'redirect' => $permiso['redirect'],
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }

        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($model->load($this->request->post()) && $model->save()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'html' => $this->renderAjax('_form_calendario_modal', [
                    'model' => $model,
                    'actionRoute' => ['editar-calendario-tarifa', 'id' => $model->id],
                    'submitLabel' => 'Guardar',
                    'modalTitle' => 'Editar tarifa',
                ]),
            ];
        }

        return $this->renderAjax('_form_calendario_modal', [
            'model' => $model,
            'actionRoute' => ['editar-calendario-tarifa', 'id' => $model->id],
            'submitLabel' => 'Guardar',
            'modalTitle' => 'Editar tarifa',
        ]);
    }

    public function actionCrearCalendarioTarifa($id_cabana)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/create',
            "Crear Tarifa",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'redirect' => $permiso['redirect'],
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }

        $cabana = Cabana::findOne((int) $id_cabana);
        if (!$cabana) {
            throw new NotFoundHttpException('Cabaña no encontrada.');
        }

        $model = Yii::createObject(Tarifa::class);
        $model->fecha = date('Y-m-d H:i:s');
        $model->activa = 1;

        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $tx = Yii::$app->db->beginTransaction();
            try {
                if ($model->load($this->request->post()) && $model->save()) {
                    $vinculo = new CabanaTarifa([
                        'id_cabana' => (int) $cabana->id,
                        'id_tarifa' => (int) $model->id,
                    ]);

                    if ($vinculo->save()) {
                        $tx->commit();
                        return ['success' => true];
                    }

                    foreach ($vinculo->getErrorSummary(true) as $error) {
                        $model->addError('descr', $error);
                    }
                }

                $tx->rollBack();
            } catch (\Throwable $e) {
                $tx->rollBack();
                $model->addError('_exception', $e->getMessage());
                Yii::error("ERROR:" . Yii::$app->controller->id . "/crear-calendario-tarifa " . $e->getMessage());
            }

            return [
                'success' => false,
                'html' => $this->renderAjax('_form_calendario_modal', [
                    'model' => $model,
                    'actionRoute' => ['crear-calendario-tarifa', 'id_cabana' => $cabana->id],
                    'submitLabel' => 'Crear y asociar',
                    'modalTitle' => 'Agregar nueva tarifa',
                ]),
            ];
        }

        return $this->renderAjax('_form_calendario_modal', [
            'model' => $model,
            'actionRoute' => ['crear-calendario-tarifa', 'id_cabana' => $cabana->id],
            'submitLabel' => 'Crear y asociar',
            'modalTitle' => 'Agregar nueva tarifa',
        ]);
    }

    private function buildTarifasDropdownByCabana(int $idCabana): array
    {
        $dropdown = [];
        foreach ($this->buildTarifasOptionsByCabana($idCabana) as $item) {
            $dropdown[$item['id']] = $item['text'];
        }

        return $dropdown;
    }

    private function buildTarifasOptionsByCabana(int $idCabana): array
    {
        if ($idCabana <= 0) {
            return [];
        }

        $tarifas = $this->findTarifasByCabana($idCabana);

        $items = [];
        foreach ($tarifas as $tarifa) {
            $items[] = [
                'id' => (int) $tarifa->id,
                'text' => sprintf(
                    '%s | %s a %s | $%s',
                    $tarifa->descr,
                    date('d-m-Y', strtotime($tarifa->inicio)),
                    date('d-m-Y', strtotime($tarifa->fin)),
                    number_format((float) $tarifa->valor_dia, 2, ',', '.')
                ),
            ];
        }

        return $items;
    }

    private function findTarifasByCabana(int $idCabana)
    {
        return Tarifa::find()->alias('t')
            ->innerJoin(['ct' => CabanaTarifa::tableName()], 'ct.id_tarifa = t.id')
            ->where(['ct.id_cabana' => $idCabana])
            ->orderBy([
                't.fecha' => SORT_DESC,
                't.created_at' => SORT_DESC,
                't.id' => SORT_DESC,
            ])
            ->all();
    }

    private function findTarifasByCabanaAndRange(int $idCabana, string $fromDate, string $toDate, int $idTarifa = 0)
    {
        $query = Tarifa::find()->alias('t')
            ->innerJoin(['ct' => CabanaTarifa::tableName()], 'ct.id_tarifa = t.id')
            ->where(['ct.id_cabana' => $idCabana])
            ->andWhere(['not', ['t.inicio' => null]])
            ->andWhere(['not', ['t.fin' => null]])
            ->andWhere(new Expression('DATE(t.inicio) <= :toDate', [':toDate' => substr($toDate, 0, 10)]))
            ->andWhere(new Expression('DATE(t.fin) >= :fromDate', [':fromDate' => substr($fromDate, 0, 10)]))
            ->orderBy([
                't.fecha' => SORT_DESC,
                't.created_at' => SORT_DESC,
                't.id' => SORT_DESC,
            ]);

        if ($idTarifa > 0) {
            $query->andWhere(['t.id' => $idTarifa]);
        }

        return $query->all();
    }


    /**
     * Displays a single Tarifa model.
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
            "Ver Tarifa",
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
     * Creates a new Tarifa model.
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
            "Crear Tarifa",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = Yii::createObject(Tarifa::class);
        $model->fecha = date('Y-m-d H:i:s'); // ← fecha y hora
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
     * Updates an existing Tarifa model.
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
            "Modificar Tarifa",
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
     * Deletes an existing Tarifa model.
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
            "Eliminar Tarifa",
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
