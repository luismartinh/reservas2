<?php
namespace app\controllers;

use app\components\asistente\Acceso;
use app\components\asistente\BorradorReserva;
use app\components\asistente\CrearReservaService;
use app\components\asistente\CargarPagoService;
use app\models\AsistentePagoForm;
use app\models\AsistenteEliminarPagoForm;
use app\models\AsistenteEliminarReservaForm;
use app\components\asistente\EliminarReservaService;
use app\components\asistente\EliminarPagoService;
use app\models\AsistenteReservaForm;
use app\components\asistente\AsistenteService;
use app\components\asistente\Configuracion;
use app\components\asistente\ContextoConversacion;
use app\components\asistente\Diagnostico;
use app\components\asistente\FuncionesReservas;
use app\components\asistente\ProveedorFactory;
use app\components\asistente\Limites;
use app\config\Niveles;
use app\models\AsistenteMensajeForm;
use app\models\Identificador;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

class AsistenteController extends Controller
{
    public function behaviors()
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => [
            'index' => ['GET'], 'mensaje' => ['POST'], 'nueva' => ['POST'], 'reservar' => ['GET', 'POST'], 'pago' => ['GET', 'POST'], 'eliminar-pago' => ['GET', 'POST'], 'eliminar-reserva' => ['GET', 'POST'], 'pasajero' => ['GET', 'POST'], 'pasajero-detalle' => ['GET'],
        ]]];
    }

    public function beforeAction($action)
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        // Debug panels and default log context may capture POST bodies and session history.
        foreach (Yii::$app->log->targets as $target) {
            $target->enabled = false;
        }
        if (Yii::$app->hasModule('debug')) {
            $debug = Yii::$app->getModule('debug');
            Yii::$app->view->off(\yii\web\View::EVENT_END_BODY, [$debug, 'renderToolbar']);
            Yii::$app->response->off(\yii\web\Response::EVENT_AFTER_PREPARE, [$debug, 'setDebugHeaders']);
        }
        try {
            Acceso::exigir(Yii::$app->user->identity);
            $request = Yii::$app->request;
            if ($request->isPost && $this->enableCsrfValidation && $request->enableCsrfValidation
                && !$request->validateCsrfToken()) {
                // Rechazo previo a cualquier acción. Sólo el navegador del administrador
                // puede leer esta respuesta del mismo origen y actualizar su token.
                $this->errorPublico(400, ['codigo' => 'csrf_invalido', 'csrf' => [
                    'param' => $request->csrfParam, 'token' => $request->getCsrfToken(),
                ]]);
                return false;
            }
            return parent::beforeAction($action); // retains CSRF and VerbFilter
        } catch (HttpException $e) {
            $this->errorPublico($e->statusCode);
            return false;
        }
    }

    private function contexto()
    {
        return new ContextoConversacion(Yii::$app->session, Configuracion::leer());
    }

    public function actionPasajeroDetalle($id)
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            throw new \yii\web\BadRequestHttpException('Pasajero inválido.');
        }
        $model = \app\models\Locador::find()->where(['id' => $id])->with('localidad.provincia')->one();
        if (!$model) { throw new \yii\web\NotFoundHttpException('El pasajero no existe.'); }
        $this->layout = 'asistente-formulario';
        return $this->render('@app/views/locador/view', ['model' => $model, 'request_get' => []]);
    }

    public function actionIndex()
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }

        $context = $this->contexto();
        $state = $context->actual(Yii::$app->user->id);
        return $this->render('index', ['initial' => $context->visible($state),
            'providerLabel' => Configuracion::leer()['proveedor'] === 'openai' ? 'OpenAI' : 'Gemini']);
    }

    public function actionNueva()
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }

        try {
            $context = $this->contexto();
            $context->exigir(Yii::$app->user->id, Yii::$app->request->post('conversacion'));
            return $this->asJson(['ok' => true] + $context->visible($context->nueva(Yii::$app->user->id)));
        } catch (HttpException $e) {
            return $this->errorPublico($e->statusCode);
        }
    }

    protected function crearServicio(array $config)
    {
        // DI seam for simulated providers in tests; no request parameter selects a provider.
        return new AsistenteService(ProveedorFactory::crear($config), new FuncionesReservas(),
            $config['bytes'], ProveedorFactory::metadata($config));
    }

    private function revalidar($userId, ContextoConversacion $context, array $snapshot)
    {
        Yii::$app->session->open();
        if ((string) Yii::$app->session->get(Yii::$app->user->idParam) !== (string) $userId) {
            throw new ForbiddenHttpException();
        }
        $identity = Identificador::findIdentity($userId);
        Acceso::exigir($identity);
        $context->exigir($userId, $snapshot['id'], $snapshot['revision']);
        return $identity;
    }

    public function actionMensaje()
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }

        $started = microtime(true);
        $diagnosticProvider = [];
        $limits = new Limites();
        try {
            $form = new AsistenteMensajeForm();
            $form->load(Yii::$app->request->post(), '');
            if (!$form->validate() || trim($form->mensaje) === '') {
                return $this->errorPublico(422);
            }
            $userId = Yii::$app->user->id;
            $config = Configuracion::leer();
            $diagnosticProvider = ProveedorFactory::metadata($config);
            $context = $this->contexto();
            $snapshot = $context->exigir($userId, $form->conversacion);
            $limits->adquirir($userId);
            $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
            $limits->consumir($config, $now);
            // Only this endpoint gets a bounded budget: cURL <=60s, PHP 75s, browser 80s.
            if (is_callable('set_time_limit')) {
                @set_time_limit(75);
            }
            Yii::$app->session->close(); // never hold PHP's session lock across HTTP
            $service = $this->crearServicio($config);
            $turn = $service->responder($snapshot, $form->mensaje, $now, function () use ($userId, $context, $snapshot) {
                return $this->revalidar($userId, $context, $snapshot);
            });
            $this->revalidar($userId, $context, $snapshot);
            if (!empty($turn['_cancel_reservation_deletion']) || ($turn['flujo_eliminar_reserva']['etapa'] ?? '') === 'reserva') {
                Yii::$app->session->remove('__asistente_eliminar_reserva');
                unset($turn['_cancel_reservation_deletion']);
            }
            if (isset($turn['_proposal']) || isset($turn['_payment']) || isset($turn['_deletion'])) {
                Yii::$app->session->remove('__asistente_eliminar_reserva');
            }
            if (isset($turn['_proposal']) || isset($turn['_payment']) || isset($turn['_deletion']) || isset($turn['_reservation_deletion'])) {
                Yii::$app->session->remove('__asistente_pasajero');
            }
            if (isset($turn['_proposal'])) {
                $draft = (new BorradorReserva(Yii::$app->session))->crear($userId, $snapshot['id'], $turn['_proposal']);
                $turn['visible'][1]['presentation']['borrador'] = $draft['token'];
                unset($turn['_proposal']);
            }
            if (($turn['flujo_pago']['etapa'] ?? '') === 'reserva' || !empty($turn['_cancel_payment'])) {
                Yii::$app->session->remove('__asistente_pago');
                Yii::$app->session->remove('__asistente_eliminar_pago');
                unset($turn['_cancel_payment']);
            }
            if (isset($turn['_payment'])) {
                Yii::$app->session->remove('__asistente_eliminar_pago');
                $draft = (new BorradorReserva(Yii::$app->session, '__asistente_pago'))->crear($userId, $snapshot['id'], $turn['_payment']);
                $turn['visible'][1]['presentation']['pago_borrador'] = $draft['token'];
                $prefill = $draft['proposal']['precarga'] ?? [];
                $received = [];
                if (isset($prefill['monto'])) { $received[] = 'Monto: $ ' . number_format((float) $prefill['monto'], 2, ',', '.'); }
                if (isset($prefill['medio_pago'])) { $received[] = 'Medio: ' . (AsistentePagoForm::medios()[$prefill['medio_pago']] ?? 'No especificado'); }
                if ($received) {
                    $turn['visible'][1]['presentation']['notes'][] = 'Datos recibidos para revisar: ' . implode(' · ', $received) . '. Todavía no se registró el pago.';
                }
                $turn['visible'][1]['presentation']['notes'][] = 'Para continuar, usá el botón «Completar y revisar pago» del bloque Pago pendiente de confirmación, debajo de la conversación y encima de Tu consulta.';
                unset($turn['_payment']);
            }
            if (isset($turn['_deletion'])) {
                Yii::$app->session->remove('__asistente_pago');
                $draft = (new BorradorReserva(Yii::$app->session, '__asistente_eliminar_pago'))->crear($userId, $snapshot['id'], $turn['_deletion']);
                $turn['visible'][1]['presentation']['eliminar_pago_borrador'] = $draft['token'];
                unset($turn['_deletion']);
            }
            if (isset($turn['_reservation_deletion'])) {
                foreach (['__asistente_pago', '__asistente_eliminar_pago', BorradorReserva::KEY] as $key) { Yii::$app->session->remove($key); }
                $draft = (new BorradorReserva(Yii::$app->session, '__asistente_eliminar_reserva'))->crear($userId, $snapshot['id'], $turn['_reservation_deletion']);
                $turn['visible'][1]['presentation']['eliminar_reserva_borrador'] = $draft['token'];
                unset($turn['_reservation_deletion']);
            }
            if (isset($turn['_passenger'])) {
                foreach (['__asistente_pago', '__asistente_eliminar_pago', '__asistente_eliminar_reserva', BorradorReserva::KEY] as $key) { Yii::$app->session->remove($key); }
                (new BorradorReserva(Yii::$app->session, '__asistente_pasajero'))->crear($userId, $snapshot['id'], $turn['_passenger']);
                unset($turn['_passenger']);
            }
            $state = $context->guardar($userId, $snapshot, $turn);
            return $this->asJson(['ok' => true, 'codigo' => $turn['code']] + $context->visible($state));
        } catch (HttpException $e) {
            if ($e->statusCode === 409 || $e->statusCode === 403) {
                Diagnostico::registrar($e->statusCode === 409 ? 'stale_conversation' : 'forbidden', $started, null, $diagnosticProvider);
            }
            return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) {
            Diagnostico::registrar('internal_error', $started, null, $diagnosticProvider);
            return $this->errorPublico(503);
        } finally {
            $limits->liberar();
            Yii::$app->session->close();
        }
    }

    /** Private human review/confirmation. No text from this form is sent to a provider. */
    public function actionReservar($borrador)
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        $limits = new Limites();
        $error = null;
        $form = new AsistenteReservaForm();
        try {
            $identity = Identificador::findIdentity(Yii::$app->user->id);
            Acceso::exigir($identity);
            $context = $this->contexto();
            $state = $context->actual($identity->id);
            $drafts = new BorradorReserva(Yii::$app->session);
            $draft = $drafts->exigir($identity->id, $state['id'], $borrador);
            $context->exigir($identity->id, $draft['conversation']);
            $service = new CrearReservaService();
            if (Yii::$app->request->isPost && empty($draft['completed'])) {
                $limits->adquirir($identity->id);
                $phase = Yii::$app->request->post('fase');
                if ($phase === 'revisar') {
                    unset($draft['review']);
                    if ($form->load(Yii::$app->request->post())) {
                        $review = $service->revisar($identity, $draft['proposal'], $form);
                        if ($review !== null) { $draft['review'] = $review; }
                    }
                    $drafts->guardar($draft);
                } elseif ($phase === 'confirmar' && !empty($draft['review'])) {
                    $id = $service->confirmar($identity, $draft);
                    // A server-created factual turn keeps subsequent "sus pagos/datos" in context.
                    $data = (new FuncionesReservas())->consultarDetalleReservas($identity,
                        ['origen' => 'seleccionadas', 'ids_reservas' => [$id], 'detalle' => 'general']);
                    $text = 'Reserva #' . $id . ' registrada sin pago inicial por confirmación del administrador.';
                    $context->guardar($identity->id, $state, [
                        'code' => 'ok', 'reserva_creada' => $id,
                        'messages' => [['role' => 'user', 'text' => 'Confirmé el formulario privado de reserva.'],
                            ['role' => 'assistant', 'text' => $text]],
                        'visible' => [['role' => 'user', 'text' => 'Confirmar reserva desde el formulario privado'],
                            ['role' => 'assistant', 'text' => $text, 'presentation' => $data['visible_presentation']]],
                    ]);
                    $draft['completed'] = $id;
                    unset($draft['review']);
                    $drafts->guardar($draft);
                } else {
                    throw new \DomainException('Completá y revisá los datos antes de confirmar.');
                }
            }
            return $this->render('reservar', ['draft' => $draft, 'form' => $form, 'error' => null]);
        } catch (\DomainException $e) {
            $error = $e->getMessage(); // Only our fixed business messages; no DB/provider exception text.
        } catch (HttpException $e) {
            return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) {
            $error = 'No se pudo completar la operación. Volvé a abrir la propuesta para comprobar su estado antes de reintentar.';
        } finally {
            $limits->liberar();
        }
        return $this->render('reservar', ['draft' => $draft ?? [], 'form' => $form, 'error' => $error]);
    }

    public function actionPasajero($borrador)
    {
        $permiso = Identificador::autorizarPorNivel(Yii::$app->user->identity, Niveles::ADMIN);
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        $modal = Yii::$app->request->get('modal') === '1';
        if ($modal) { $this->layout = 'asistente-formulario'; }
        $limits = new Limites();
        $model = null; $error = null; $estado = null; $draft = [];
        try {
            $identity = Identificador::findIdentity(Yii::$app->user->id);
            Acceso::exigir($identity);
            $context = $this->contexto();
            $state = $context->actual($identity->id);
            $drafts = new BorradorReserva(Yii::$app->session, '__asistente_pasajero');
            $draft = $drafts->exigir($identity->id, $state['id'], $borrador);
            $context->exigir($identity->id, $draft['conversation']);
            $service = new \app\components\asistente\EditarPasajeroService();
            $phase = Yii::$app->request->isPost ? Yii::$app->request->post('fase') : null;
            if (Yii::$app->request->isPost && empty($draft['completed'])) {
                $limits->adquirir($identity->id);
                $oldReview = $draft['review'] ?? null;
                if ($phase !== 'confirmar') { unset($draft['review']); $drafts->guardar($draft); }
                if ($phase === 'buscar') {
                    $draft['proposal']['ids_locadores'] = $service->buscar($identity, Yii::$app->request->post('buscar'), Yii::$app->request->post('campo', 'todos'));
                    unset($draft['id_locador'], $draft['base']);
                } elseif ($phase === 'seleccionar') {
                    $selected = Yii::$app->request->post('id_locador');
                    if (!is_scalar($selected) || !preg_match('/^[1-9][0-9]*$/D', (string) $selected)
                        || !in_array((int) $selected, $draft['proposal']['ids_locadores'], true)) {
                        throw new \DomainException('Seleccioná uno de los pasajeros encontrados.');
                    }
                    $draft['id_locador'] = (int) $selected;
                    unset($draft['base']);
                } elseif ($phase === 'recargar') {
                    unset($draft['base']);
                } elseif ($phase === 'revisar') {
                    $model = $service->cargar($identity, $draft['id_locador'] ?? 0);
                    $input = Yii::$app->request->post('Locador', []);
                    if (!is_array($input)) { throw new \DomainException('Datos inválidos.'); }
                    $review = $service->revisar($identity, $draft, $model, $input);
                    if ($review !== null) { $draft['review'] = $review; }
                } elseif ($phase === 'editar') {
                    $model = $service->cargar($identity, $draft['id_locador'] ?? 0);
                    if ($oldReview) { $model->setAttributes($oldReview['valores']); }
                } elseif ($phase === 'confirmar' && !empty($draft['review'])) {
                    $id = $service->confirmar($identity, $draft);
                    $draft['completed'] = $id;
                    unset($draft['review']);
                    $drafts->guardar($draft);
                    $text = ($draft['proposal']['operacion'] === 'calificacion' ? 'Calificación y nota' : 'Datos')
                        . ' del pasajero #' . $id . ' guardados por confirmación del administrador.';
                    $context->guardar($identity->id, $state, ['code' => 'ok', 'pasajero_editado' => $id,
                        'messages' => [['role' => 'user', 'text' => 'Confirmé la edición del pasajero en el formulario privado.'],
                            ['role' => 'assistant', 'text' => $text]],
                        'visible' => [['role' => 'user', 'text' => 'Confirmar edición del pasajero'], ['role' => 'assistant', 'text' => $text]],
                    ]);
                } else { throw new \DomainException('Completá y revisá el formulario antes de guardar.'); }
                $drafts->guardar($draft);
            }
            if (empty($draft['completed'])) {
                if (!isset($draft['id_locador']) && count($draft['proposal']['ids_locadores']) === 1) {
                    $draft['id_locador'] = $draft['proposal']['ids_locadores'][0];
                }
                if (isset($draft['id_locador'])) {
                    if (!$model) { $model = $service->cargar($identity, $draft['id_locador']); }
                    if (!isset($draft['base'])) { $draft['base'] = $service->huella($model); }
                }
                $drafts->guardar($draft);
            } else { $estado = $context->visible($context->actual($identity->id)); }
        } catch (\DomainException $e) { $error = $e->getMessage();
        } catch (HttpException $e) { return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) { $error = 'No se pudo completar la edición. Recargá el formulario para comprobar su estado antes de reintentar.';
        } finally { $limits->liberar(); }
        $candidates = [];
        if (!$model && isset($draft['id_locador']) && empty($draft['completed'])) {
            $model = \app\models\Locador::findOne($draft['id_locador']);
        }
        if (empty($draft['id_locador']) && !empty($draft['proposal']['ids_locadores'])) {
            $candidates = \app\models\Locador::find()->with('localidad.provincia')->where(['id' => $draft['proposal']['ids_locadores']])->orderBy('denominacion')->all();
        }
        return $this->render('pasajero', compact('draft', 'model', 'error', 'modal', 'estado', 'candidates'));
    }

    public function actionPago($borrador)
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        $limits = new Limites();
        $form = new AsistentePagoForm();
        $request = null;
        try {
            $identity = Identificador::findIdentity(Yii::$app->user->id);
            Acceso::exigir($identity);
            $context = $this->contexto();
            $state = $context->actual($identity->id);
            $drafts = new BorradorReserva(Yii::$app->session, '__asistente_pago');
            $draft = $drafts->exigir($identity->id, $state['id'], $borrador);
            $context->exigir($identity->id, $draft['conversation']);
            if (Yii::$app->request->isGet && empty($draft['review']) && empty($draft['completed'])) {
                foreach (['monto', 'medio_pago'] as $field) {
                    if (isset($draft['proposal']['precarga'][$field])) { $form->$field = $draft['proposal']['precarga'][$field]; }
                }
            }
            $service = new CargarPagoService();
            if (Yii::$app->request->isPost && empty($draft['completed'])) {
                $limits->adquirir($identity->id);
                $phase = Yii::$app->request->post('fase');
                if ($phase === 'revisar') {
                    unset($draft['review']);
                    $drafts->guardar($draft);
                    if ($form->load(Yii::$app->request->post())) {
                        $form->comprobante = \yii\web\UploadedFile::getInstance($form, 'comprobante');
                        $review = $service->revisar($identity, $draft['proposal'], $form);
                        if ($review !== null) {
                            $service->adjuntar($identity, $review, $form, $draft['token']);
                            $draft['review'] = $review;
                            $drafts->guardar($draft);
                        }
                    }
                } elseif ($phase === 'confirmar' && !empty($draft['review'])) {
                    $id = $service->confirmar($identity, $draft);
                    $draft['completed'] = $id;
                    unset($draft['review']);
                    $drafts->guardar($draft);
                    $data = (new FuncionesReservas())->consultarPagosReservas($identity,
                        ['origen' => 'seleccionadas', 'ids_reservas' => [$id]]);
                    $text = 'Yii registró el pago de la reserva #' . $id . ' por confirmación del administrador.';
                    $context->guardar($identity->id, $state, ['code' => 'ok', 'reserva_pago' => $id,
                        'flujo_pago' => ['etapa' => 'cerrado'], 'flujo_reserva' => ['etapa' => 'cerrado'],
                        'messages' => [['role' => 'user', 'text' => 'Confirmé el formulario privado de pago.'],
                            ['role' => 'assistant', 'text' => $text]],
                        'visible' => [['role' => 'user', 'text' => 'Confirmar pago desde el formulario privado'],
                            ['role' => 'assistant', 'text' => $text, 'presentation' => $data['visible_presentation']]],
                    ]);
                } else { throw new \DomainException('Completá y revisá el pago antes de confirmar.'); }
            }
            if (empty($draft['completed']) && empty($draft['review'])) {
                $request = $service->solicitud($identity, $draft['proposal']['id_reserva']);
            }
            return $this->renderPago(['draft' => $draft, 'form' => $form, 'request' => $request, 'error' => null]);
        } catch (\DomainException $e) {
            $error = $e->getMessage();
        } catch (HttpException $e) { return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) {
            $error = 'No se pudo completar la operación. Volvé a abrir el formulario para comprobar su estado antes de reintentar.';
        } finally { $limits->liberar(); }
        return $this->renderPago(['draft' => $draft ?? [], 'form' => $form, 'request' => $request, 'error' => $error]);
    }

    public function actionEliminarPago($borrador)
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        $limits = new Limites();
        $form = new AsistenteEliminarPagoForm();
        $request = null; $payments = [];
        try {
            $identity = Identificador::findIdentity(Yii::$app->user->id);
            Acceso::exigir($identity);
            $context = $this->contexto(); $state = $context->actual($identity->id);
            $drafts = new BorradorReserva(Yii::$app->session, '__asistente_eliminar_pago');
            $draft = $drafts->exigir($identity->id, $state['id'], $borrador);
            $context->exigir($identity->id, $draft['conversation']);
            $service = new EliminarPagoService();
            if (Yii::$app->request->isPost && empty($draft['completed'])) {
                $limits->adquirir($identity->id);
                $phase = Yii::$app->request->post('fase');
                if ($phase === 'revisar') {
                    unset($draft['review']); $drafts->guardar($draft);
                    if ($form->load(Yii::$app->request->post())) {
                        $review = $service->revisar($identity, $draft['proposal'], $form);
                        if ($review !== null) { $draft['review'] = $review; $drafts->guardar($draft); }
                    }
                } elseif ($phase === 'confirmar' && !empty($draft['review'])) {
                    $id = $service->confirmar($identity, $draft);
                    $draft['completed'] = $id; unset($draft['review']); $drafts->guardar($draft);
                    $data = (new FuncionesReservas())->consultarPagosReservas($identity,
                        ['origen' => 'seleccionadas', 'ids_reservas' => [$id]]);
                    $text = 'Yii eliminó el pago seleccionado de la reserva #' . $id . ' por confirmación del administrador.';
                    $context->guardar($identity->id, $state, ['code' => 'ok', 'reserva_pago' => $id,
                        'flujo_pago' => ['etapa' => 'cerrado'], 'flujo_reserva' => ['etapa' => 'cerrado'],
                        'messages' => [['role' => 'user', 'text' => 'Confirmé la eliminación de un pago en el formulario privado.'], ['role' => 'assistant', 'text' => $text]],
                        'visible' => [['role' => 'user', 'text' => 'Confirmar eliminación del pago seleccionado'],
                            ['role' => 'assistant', 'text' => $text, 'presentation' => $data['visible_presentation']]],
                    ]);
                } else { throw new \DomainException('Seleccioná y revisá el pago antes de confirmar su eliminación.'); }
            }
            if (empty($draft['completed']) && empty($draft['review'])) {
                $request = $service->solicitud($identity, $draft['proposal']['id_reserva']);
                $payments = $service->pagos($identity, $request);
            }
            return $this->renderPago(['draft' => $draft, 'form' => $form, 'request' => $request, 'payments' => $payments, 'error' => null], 'eliminar-pago');
        } catch (\DomainException $e) { $error = $e->getMessage();
        } catch (HttpException $e) { return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) { $error = 'No se pudo completar la operación. Comprobá el estado del pago antes de reintentar.';
        } finally { $limits->liberar(); }
        return $this->renderPago(['draft' => $draft ?? [], 'form' => $form, 'request' => $request, 'payments' => $payments, 'error' => $error], 'eliminar-pago');
    }

    public function actionEliminarReserva($borrador)
    {
        $permiso = Identificador::autorizarPorNivel(
            Yii::$app->user->identity,
            Niveles::ADMIN
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t('app', $permiso['msg']));
            return $this->redirect($permiso['redirect']);
        }
        $limits = new Limites(); $form = new AsistenteEliminarReservaForm(); $quote = null;
        try {
            $identity = Identificador::findIdentity(Yii::$app->user->id); Acceso::exigir($identity);
            $context = $this->contexto(); $state = $context->actual($identity->id);
            $drafts = new BorradorReserva(Yii::$app->session, '__asistente_eliminar_reserva');
            $draft = $drafts->exigir($identity->id, $state['id'], $borrador);
            $context->exigir($identity->id, $draft['conversation']);
            $service = new EliminarReservaService();
            if (Yii::$app->request->isPost && empty($draft['completed'])) {
                $limits->adquirir($identity->id);
                $phase = Yii::$app->request->post('fase');
                if ($phase === 'revisar') {
                    unset($draft['review']); $drafts->guardar($draft);
                    if ($form->load(Yii::$app->request->post())) {
                        $review = $service->revisar($identity, $draft['proposal'], $form);
                        if ($review !== null) { $draft['review'] = $review; $drafts->guardar($draft); }
                    }
                } elseif ($phase === 'confirmar' && !empty($draft['review'])) {
                    $result = $service->confirmar($identity, $draft); $id = $result['id_reserva'];
                    $draft['completed'] = $id; $draft['eliminada'] = $result['eliminada']; unset($draft['review']); $drafts->guardar($draft);
                    foreach (['__asistente_pago', '__asistente_eliminar_pago', BorradorReserva::KEY] as $key) { Yii::$app->session->remove($key); }
                    $text = $result['eliminada']
                        ? 'Yii confirmó que la reserva #' . $id . ' fue eliminada. Se quitó su ocupación; las solicitudes y pagos se conservaron desvinculados.'
                        : 'La reserva #' . $id . ' ya no está registrada. No se realizaron cambios adicionales.';
                    $context->guardar($identity->id, $state, ['code' => 'ok', 'reserva_eliminada' => $id,
                        'flujo_reserva' => ['etapa' => 'cerrado'], 'flujo_pago' => ['etapa' => 'cerrado'], 'flujo_eliminar_reserva' => ['etapa' => 'cerrado'],
                        'messages' => [['role' => 'user', 'text' => 'Confirmé la eliminación de la reserva en el formulario privado.'], ['role' => 'assistant', 'text' => $text]],
                        'visible' => [['role' => 'user', 'text' => 'Confirmar eliminación de la reserva #' . $id], ['role' => 'assistant', 'text' => $text]],
                    ]);
                } else { throw new \DomainException('Revisá el impacto antes de confirmar la eliminación de la reserva.'); }
            }
            if (empty($draft['completed']) && empty($draft['review'])) { $quote = $service->inspeccionar($identity, $draft['proposal']['id_reserva']); }
            return $this->renderPago(['draft' => $draft, 'form' => $form, 'quote' => $quote, 'error' => null], 'eliminar-reserva');
        } catch (\DomainException $e) { $error = $e->getMessage();
        } catch (HttpException $e) { return $this->errorPublico($e->statusCode);
        } catch (\Throwable $e) { $error = 'No se pudo completar la operación. Comprobá el estado de la reserva antes de reintentar.';
        } finally { $limits->liberar(); }
        return $this->renderPago(['draft' => $draft ?? [], 'form' => $form, 'quote' => $quote, 'error' => $error], 'eliminar-reserva');
    }

    private function renderPago(array $params, $view = 'pago')
    {
        $modal = Yii::$app->request->isAjax && Yii::$app->request->get('modal') === '1';
        $params['modal'] = $modal;
        if (!$modal) { return $this->render($view, $params); }
        $completed = !empty($params['draft']['completed']);
        $response = ['ok' => $params['error'] === null, 'completado' => $completed,
            // Trusted, escaped Yii view only. No model output is rendered as HTML.
            'html' => $this->renderPartial($view, $params)];
        if ($completed) {
            $context = $this->contexto();
            $response['estado'] = $context->visible($context->actual(Yii::$app->user->id));
        }
        return $this->asJson($response);
    }

    private function errorPublico($status, array $details = [])
    {
        $messages = [
            400 => 'No se pudo validar la seguridad de la solicitud. Recargá la página e intentá nuevamente.',
            403 => 'Acceso exclusivo para administradores activos.',
            405 => 'Método no permitido.',
            409 => 'La conversación cambió o venció. Recargá la página para continuar.',
            413 => 'El intercambio excede el límite de contexto. Acortá el mensaje o iniciá una nueva conversación.',
            422 => 'Ingresá un mensaje de hasta 2.000 caracteres y una conversación válida.',
            429 => 'Ya hay una consulta en curso o se alcanzó el límite de llamadas. Intentá más tarde.',
        ];
        Yii::$app->response->statusCode = $status;
        return $this->asJson(['ok' => false, 'mensaje' => $messages[$status] ?? 'No se pudo procesar la consulta. Intentá más tarde.'] + $details);
    }
}
