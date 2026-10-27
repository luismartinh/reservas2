<?php
namespace app\components\asistente;

class AsistenteService
{
    private $provider;
    private $functions;
    private $maxBytes;
    private $metadata;
    private $providerLabel;

    public function __construct(ProveedorInterface $provider, FuncionesReservas $functions, $maxBytes = 24576, array $metadata = [])
    {
        $this->provider = $provider;
        $this->functions = $functions;
        $this->maxBytes = $maxBytes;
        $this->metadata = ProveedorFactory::metadata(['proveedor' => $metadata['provider'] ?? 'gemini',
            'openaiModelo' => $metadata['model'] ?? 'gpt-5.4-mini']);
        $this->providerLabel = $this->metadata['provider'] === 'openai' ? 'OpenAI' : 'Gemini';
    }

    /** Narrow fallback for an explicit whole-month listing; never drops additional filters. */
    private function reservasMesSinFuncion(array $result, array $user, \DateTimeImmutable $now, callable $freshIdentity, $started)
    {
        if (empty($result['ok']) || !empty($result['tool_calls'])) { return null; }
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
            'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $user['text'])));
        $pattern = '/^(?:(?:ver|mostrame|mostrá|mostrar|muéstrame|muestrame|dame|dime|listar|lista) )?(?:las )?reservas '
            . '(?:de|en|durante) (?:el )?(?:mes de )?(' . implode('|', $months) . ')'
            . '(?: (?:de |del |del año |del ano )?([1-9][0-9]{3})| (pasado|próximo|proximo|del corriente año|de este año|del año pasado|del próximo año))?[.!?]*$/u';
        if (!preg_match($pattern, $text, $match)) { return null; }
        $year = !empty($match[2]) ? (int) $match[2] : (int) $now->format('Y');
        $relative = $match[3] ?? '';
        if (strpos($relative, 'año pasado') !== false) { $year--; }
        elseif (strpos($relative, 'próximo año') !== false) { $year++; }
        elseif (in_array($relative, ['pasado', 'próximo', 'proximo'], true)) {
            $monthNumber = array_search($match[1], $months, true) + 1;
            if ($relative === 'pasado' && $monthNumber > (int) $now->format('n')) { $year--; }
            elseif ($relative !== 'pasado' && $monthNumber < (int) $now->format('n')) { $year++; }
        }
        $from = sprintf('%04d-%02d-01', $year, array_search($match[1], $months, true) + 1);
        $args = ['desde' => $from, 'hasta' => (new \DateTimeImmutable($from, $now->getTimezone()))->format('Y-m-t')];
        $turn = ['code' => 'ok'];
        try {
            $data = $this->functions->listarReservasPorEstadia($freshIdentity(), $args);
            $presentation = $data['visible_presentation'];
            unset($data['visible_presentation']);
            $turn['seleccion_reservas'] = $data;
            $turn['messages'] = [$user, ['role' => 'assistant', 'text' => $data['text']]];
            $turn['visible'] = [$user, ['role' => 'assistant', 'text' => $data['text'], 'presentation' => $presentation]];
        } catch (\yii\web\HttpException $e) { throw $e;
        } catch (\Throwable $e) {
            $turn['code'] = 'query_error';
            $turn['messages'] = [$user, ['role' => 'assistant',
                'text' => 'No se pudo completar la consulta. No se obtuvieron resultados; intentá más tarde.']];
            $turn['visible'] = $turn['messages'];
        }
        if (max(strlen(serialize($turn)), strlen(json_encode($turn, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE))) + 1024 > $this->maxBytes) {
            throw new \yii\web\HttpException(413, 'context_limit');
        }
        Diagnostico::registrar($turn['code'], $started, $result['usage'] ?? null, $this->metadata);
        return $turn;
    }

    private function responderInicioPago(array $user, array $step, callable $freshIdentity, $started)
    {
        $identity = $freshIdentity();
        Acceso::exigir($identity);
        $removing = ($step['operacion'] ?? '') === 'eliminar';
        $turn = ['code' => 'ok', 'flujo_reserva' => ['etapa' => 'cerrado'], 'flujo_pago' => ['etapa' => $step['etapa'], 'operacion' => $removing ? 'eliminar' : 'agregar'], 'flujo_eliminar_reserva' => ['etapa' => 'cerrado']];
        if (!empty($step['precarga'])) { $turn['flujo_pago']['precarga'] = $step['precarga']; }
        if (isset($step['listar_titular'])) { $turn['flujo_pago']['titular'] = $step['listar_titular']; }
        if ($step['etapa'] === 'cerrado') { $turn['_cancel_payment'] = true; }
        $data = null;
        try {
            if (isset($step['listar_titular'])) {
                $data = $this->functions->listarReservasPorTitular($identity, ['titular' => $step['listar_titular']]);
                $data['visible_presentation']['notes'][] = $removing
                    ? 'Para continuar con la eliminación, indicá el número de una reserva. Después elegirás el pago; todavía no se eliminó ninguno.'
                    : 'Para continuar con la carga del pago, elegí e indicá el número de una reserva. Todavía no se registró ningún pago.';
                $selection = $data; unset($selection['visible_presentation']);
                $turn['seleccion_reservas'] = $selection;
                $reply = $data['text'];
            } elseif (isset($step['args'])) {
                if ($removing) {
                    $data = $this->functions->prepararEliminacionPagoReserva($identity, $step['args']);
                    $turn['_deletion'] = $data['visible_deletion'];
                    $turn['reserva_pago'] = $data['visible_deletion']['id_reserva'];
                } else {
                    $data = $this->functions->prepararPagoReserva($identity, $step['args']);
                    $turn['_payment'] = $data['visible_payment'];
                    if (!empty($step['precarga'])) { $turn['_payment']['precarga'] = $step['precarga']; }
                    $turn['reserva_pago'] = $data['visible_payment']['id_reserva'];
                }
                $reply = $data['text'];
            } else { $reply = $step['text']; }
        } catch (\InvalidArgumentException $e) {
            $reply = 'Indicá una sola reserva por su número para cargar el pago.';
            $turn['flujo_pago'] = ['etapa' => 'reserva', 'operacion' => $removing ? 'eliminar' : 'agregar'];
        } catch (\DomainException $e) {
            $reply = $e->getMessage();
            $turn['flujo_pago'] = ['etapa' => 'reserva', 'operacion' => $removing ? 'eliminar' : 'agregar'];
        } catch (\yii\web\HttpException $e) { throw $e;
        } catch (\Throwable $e) {
            $turn['code'] = 'query_error';
            $reply = 'No se pudo preparar la operación del pago. No se aplicaron cambios. Intentá nuevamente.';
            $turn['flujo_pago'] = ['etapa' => 'reserva', 'operacion' => $removing ? 'eliminar' : 'agregar'];
        }
        $turn['messages'] = [$user, ['role' => 'assistant', 'text' => $reply]];
        $turn['visible'] = $turn['messages'];
        if (isset($data['visible_presentation'])) { $turn['visible'][1]['presentation'] = $data['visible_presentation']; }
        if (max(strlen(serialize($turn)), strlen(json_encode($turn, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE))) + 1024 > $this->maxBytes) {
            throw new \yii\web\HttpException(413, 'context_limit');
        }
        Diagnostico::registrar($turn['code'], $started, null, $this->metadata);
        return $turn;
    }

    private function responderEliminarReserva(array $user, array $step, callable $freshIdentity, $started)
    {
        $identity = $freshIdentity(); Acceso::exigir($identity);
        $turn = ['code' => 'ok', 'flujo_eliminar_reserva' => ['etapa' => $step['etapa']],
            'flujo_reserva' => ['etapa' => 'cerrado'], 'flujo_pago' => ['etapa' => 'cerrado']];
        if ($step['etapa'] === 'cerrado') { $turn['_cancel_reservation_deletion'] = true; }
        $data = null;
        try {
            if (isset($step['listar_titular'])) {
                $data = $this->functions->listarReservasPorTitular($identity, ['titular' => $step['listar_titular']]);
                $data['visible_presentation']['notes'][] = 'Indicá el número de la reserva cargada por error para revisar su eliminación. Todavía no se eliminó ninguna.';
                $selection = $data; unset($selection['visible_presentation']);
                $turn['seleccion_reservas'] = $selection;
                $text = $data['text'];
            } elseif (isset($step['args'])) {
                $data = $this->functions->prepararEliminacionReserva($identity, $step['args']);
                $turn['_reservation_deletion'] = $data['visible_reservation_deletion'];
                $text = $data['text'];
            } else { $text = $step['text']; }
        } catch (\InvalidArgumentException $e) {
            $text = 'Indicá una sola reserva por su número para revisar su eliminación.';
            $turn['flujo_eliminar_reserva'] = ['etapa' => 'reserva'];
        } catch (\DomainException $e) {
            $text = $e->getMessage(); $turn['flujo_eliminar_reserva'] = ['etapa' => 'reserva'];
        } catch (\yii\web\HttpException $e) { throw $e;
        } catch (\Throwable $e) {
            $text = 'No se pudo preparar la eliminación de la reserva. No se aplicaron cambios.';
            $turn['code'] = 'query_error'; $turn['flujo_eliminar_reserva'] = ['etapa' => 'reserva'];
        }
        $turn['messages'] = [$user, ['role' => 'assistant', 'text' => $text]];
        $turn['visible'] = $turn['messages'];
        if (isset($data['visible_presentation'])) { $turn['visible'][1]['presentation'] = $data['visible_presentation']; }
        if (max(strlen(serialize($turn)), strlen(json_encode($turn, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE))) + 1024 > $this->maxBytes) {
            throw new \yii\web\HttpException(413, 'context_limit');
        }
        Diagnostico::registrar($turn['code'], $started, null, $this->metadata);
        return $turn;
    }

    /** $freshIdentity is resolved AFTER the external request and before any DB function. */
    public function responder(array $state, $text, \DateTimeImmutable $now, callable $freshIdentity)
    {
        $started = microtime(true);
        $messages = [];
        $previous = null;
        $previousReservations = null;
        $previousPassengers = null;
        $flow = [];
        $paymentFlow = [];
        $reservationDeletionFlow = [];
        foreach ($state['turns'] as $turn) {
            if (isset($turn['seleccion_reservas']) || isset($turn['reserva_pago']) || isset($turn['reserva_creada'])) { $previousPassengers = null; }
            if (isset($turn['pasajero_editado'])) { $previousPassengers = [['id' => (int) $turn['pasajero_editado']]]; }
            $messages = array_merge($messages, $turn['messages']);
            if (isset($turn['flujo_eliminar_reserva'])) { $reservationDeletionFlow = $turn['flujo_eliminar_reserva']; }
            if (isset($turn['reserva_eliminada'])) { $previousReservations = null; $previous = null; }
            if (isset($turn['flujo_pago'])) { $paymentFlow = $turn['flujo_pago']; }
            if (isset($turn['seleccion_reservas'])) {
                $previousReservations = $turn['seleccion_reservas'];
                $previous = null;
            }
            if (isset($turn['reserva_pago'])) {
                $previousReservations = ['ok' => true, 'reservas' => [['id' => $turn['reserva_pago']]], 'truncado' => false];
                $previous = null;
            }
            if (isset($turn['flujo_reserva'])) { $flow = $turn['flujo_reserva']; }
            else {
                // Recover pending dates from conversations started before explicit workflow state existed.
                foreach ($turn['messages'] as $oldMessage) {
                    if (($oldMessage['role'] ?? '') === 'user') {
                        $oldStep = FlujoReserva::continuar($oldMessage['text'] ?? '', $flow, $now);
                        if ($oldStep !== null) { $flow = $oldStep['flow']; }
                        break;
                    }
                }
            }
            if (isset($turn['seleccion_reserva'])) {
                $previous = $turn['seleccion_reserva'];
                $previousReservations = null;
            }
            if (isset($turn['reserva_creada'])) {
                $previousReservations = ['ok' => true, 'reservas' => [['id' => $turn['reserva_creada']]], 'truncado' => false];
                $previous = null;
                $flow = ['etapa' => 'cerrado'];
            }
            foreach ($turn['messages'] as $message) {
                if ($message['role'] === 'tool' && in_array($message['name'] ?? '', ['preparar_edicion_pasajero', 'buscar_pasajeros'], true) && !empty($message['data']['ok'])) {
                    $previousPassengers = $message['data']['pasajeros'];
                    $previous = null;
                    $previousReservations = null;
                }
                if (!$flow && $message['role'] === 'tool' && ($message['name'] ?? '') === 'iniciar_reserva'
                    && !empty($message['data']['ok']) && !isset($message['data']['cabanas'])) {
                    $flow = ['etapa' => 'periodo'];
                }
                if ($message['role'] === 'tool' && in_array($message['name'] ?? '',
                    ['consultar_ultima_reserva', 'listar_reservas_por_titular', 'listar_reservas_registradas', 'listar_reservas_por_estadia', 'listar_reservas_por_ingreso', 'listar_reservas_impagas', 'consultar_detalle_reservas', 'consultar_pagos_reservas'], true)
                    && !empty($message['data']['ok'])) {
                    $previous = null; // A reservation list is not a cabin selection for a later price question.
                    $previousReservations = $message['data'];
                    $previousPassengers = null;
                }
                if ($message['role'] === 'tool' && in_array($message['name'] ?? '',
                    ['consultar_disponibilidad', 'consultar_tarifas', 'preparar_reserva', 'iniciar_reserva'], true) && !empty($message['data']['ok']) && isset($message['data']['cabanas'])) {
                    $previous = $message['data'];
                    $previousReservations = null;
                }
                if ($message['role'] === 'tool' && ($message['name'] ?? '') === 'listar_tarifas_cabana' && !empty($message['data']['ok']) && isset($message['data']['cabanas'])) {
                    $previous = $message['data'];
                    $previous['truncado'] = !empty($message['data']['cabanas_truncadas']); // Rate-row truncation does not change cabin selection.
                    $previousReservations = null;
                }
            }
        }
        $user = ['role' => 'user', 'text' => $text];
        $messages[] = $user;
        if (FlujoPago::inicio($text) || FlujoPago::eliminar($text) || FlujoReserva::inicio($text)) { $reservationDeletionFlow = ['etapa' => 'cerrado']; }
        $deletionStep = FlujoEliminarReserva::continuar($text, $reservationDeletionFlow, $previousReservations);
        if ($deletionStep !== null) { return $this->responderEliminarReserva($user, $deletionStep, $freshIdentity, $started); }
        $paymentStep = FlujoPago::continuar($text, $paymentFlow, $previousReservations);
        if ($paymentStep !== null) {
            return $this->responderInicioPago($user, $paymentStep, $freshIdentity, $started);
        }
        if (FlujoReserva::inicio($text)) { $paymentFlow = ['etapa' => 'cerrado']; }
        $step = FlujoReserva::continuar($text, $flow, $now);
        if ($step !== null) {
            $identity = $freshIdentity();
            $turn = ['code' => 'ok', 'flujo_reserva' => $step['flow'], 'flujo_pago' => ['etapa' => 'cerrado'], 'flujo_eliminar_reserva' => ['etapa' => 'cerrado']];
            $data = null;
            try {
                if (isset($step['args'])) {
                    $data = $this->functions->iniciarReserva($identity, $step['args']);
                    $reply = $data['text'];
                    $turn['flujo_reserva']['desde'] = $data['desde'];
                    $turn['flujo_reserva']['hasta'] = $data['hasta'];
                    $selection = $data;
                    unset($selection['visible_presentation'], $selection['visible_text']);
                    $turn['seleccion_reserva'] = $selection;
                } else { $reply = $step['text']; }
            } catch (\InvalidArgumentException $e) {
                $turn['code'] = 'invalid_arguments';
                $turn['flujo_reserva'] = ['etapa' => 'periodo'];
                $reply = 'Las fechas no son válidas. Indicá fechas reales de ingreso y salida, con salida posterior al ingreso y mínimo un día.';
            } catch (\yii\web\HttpException $e) { throw $e;
            } catch (\Throwable $e) {
                $turn['code'] = 'query_error';
                $turn['flujo_reserva'] = $flow ?: ['etapa' => 'periodo'];
                $reply = 'No se pudo consultar disponibilidad y precios. No se guardó ninguna reserva. Volvé a enviar el período.';
            }
            // These are server replies, not invented model calls or synthetic provider output IDs.
            $turn['messages'] = [$user, ['role' => 'assistant', 'text' => $reply]];
            $turn['visible'] = $turn['messages'];
            if (isset($data['visible_presentation'])) { $turn['visible'][1]['presentation'] = $data['visible_presentation']; }
            $bytes = max(strlen(serialize($turn)), strlen(json_encode($turn, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)));
            if ($bytes + 1024 > $this->maxBytes) { throw new \yii\web\HttpException(413, 'context_limit'); }
            Diagnostico::registrar($turn['code'], $started, null, $this->metadata);
            return $turn;
        }
        $result = $this->provider->interpretar($messages, $now);
        $monthTurn = $this->reservasMesSinFuncion($result, $user, $now, $freshIdentity, $started);
        if ($monthTurn !== null) { return $monthTurn; }
        $code = 'provider_error';
        $reply = 'No se pudo completar la comunicación con ' . $this->providerLabel . '. '
            . 'Volvé a enviar tu consulta en unos momentos, en esta misma conversación.';
        $unit = [$user];
        $internalReply = null;
        $presentation = null;
        $proposal = null;
        $payment = null;
        $deletion = null;
        $reservationDeletion = null;
        $passenger = null;
        if (!empty($result['ok'])) {
            $calls = $result['tool_calls'] ?? [];
            if (!$calls) {
                // Model prose can contain hallucinated facts: it is never presented as a factual answer.
                $code = 'clarification';
                $reply = 'Puedo consultar disponibilidad, tarifas y reservas por estadía, fecha de registro o ingreso. Indicá el período '
                    . 'y qué querés consultar. Para listar las tarifas configuradas, basta indicar el número de cabaña. '
                    . 'También podés pedir reservas impagas sin fechas ni IDs, o titulares, datos, contacto y pagos de las reservas encontradas. '
                    . 'Para preparar una reserva indicá las cabañas y el período; los datos del pasajero se completan en el formulario privado. '
                    . 'También podés editar los datos de un pasajero o su calificación y nota en un formulario privado.';
                if (($reservationDeletionFlow['etapa'] ?? '') === 'reserva') {
                    $reply = 'Seguimos revisando una reserva cargada por error. Indicá su número o pedí buscar sus reservas por titular.';
                } elseif (($paymentFlow['etapa'] ?? '') === 'reserva') {
                    $reply = ($paymentFlow['operacion'] ?? '') === 'eliminar'
                        ? 'Seguimos con la eliminación de un pago. Indicá el número de reserva o pedí buscar sus reservas por titular; después seleccionarás el movimiento.'
                        : 'Seguimos con la carga del pago. Indicá el número de reserva, por ejemplo «reserva 81». Monto y comprobante se completan en el formulario privado.';
                } elseif (in_array($flow['etapa'] ?? '', ['periodo', 'anio'], true)) {
                    $reply = FlujoReserva::pregunta($flow);
                } elseif (FlujoReserva::inicio($text)) {
                    $reply = 'Para iniciar la reserva necesito las fechas de ingreso y salida con día, mes y año. '
                        . 'Si ya indicaste los días y el mes, confirmá el año. Después te mostraré disponibilidad y precios para elegir las cabañas.';
                }
                $unit[] = ['role' => 'assistant', 'text' => '', 'provider_state' => $result['provider_state']];
            } elseif (count($calls) !== 1 || !in_array($calls[0]['name'] ?? '',
                ['buscar_pasajeros', 'preparar_edicion_pasajero', 'consultar_ultima_reserva', 'preparar_eliminacion_reserva', 'preparar_eliminacion_pago_reserva', 'listar_reservas_por_titular', 'preparar_pago_reserva', 'iniciar_reserva', 'preparar_reserva', 'consultar_disponibilidad', 'consultar_tarifas', 'listar_tarifas_cabana', 'listar_reservas_registradas', 'listar_reservas_por_estadia', 'listar_reservas_por_ingreso', 'listar_reservas_impagas', 'consultar_detalle_reservas', 'consultar_pagos_reservas'], true)) {
                $code = 'unsupported_calls';
                $reply = 'Solo puedo ejecutar una consulta de disponibilidad, tarifas o reservas por mensaje. Reformulá la consulta.';
            } else {
                $call = $calls[0];
                try {
                    if (!is_array($call['arguments'] ?? null)) {
                        throw new \InvalidArgumentException('Argumentos inválidos.');
                    }
                    $tariffs = $call['name'] === 'consultar_tarifas';
                    $details = $call['name'] === 'consultar_detalle_reservas';
                    $payments = $call['name'] === 'consultar_pagos_reservas';
                    $args = $call['arguments'];
                    if ($call['name'] === 'listar_reservas_impagas') {
                        $requestedName = $this->functions->titularImpagasEnTexto($text);
                        if ($requestedName !== null) { $args['titular'] = $requestedName; }
                    }
                    if ($call['name'] === 'buscar_pasajeros') {
                        (new BuscarPasajerosService())->validar($args);
                    } elseif ($call['name'] === 'preparar_edicion_pasajero') {
                        (new EditarPasajeroService())->validar($args);
                    } elseif ($call['name'] === 'consultar_ultima_reserva') {
                        $this->functions->validarUltimaReserva($args);
                    } elseif ($call['name'] === 'listar_reservas_por_titular') {
                        $this->functions->validarReservasPorTitular($args);
                    } elseif (in_array($call['name'], ['preparar_pago_reserva', 'preparar_eliminacion_pago_reserva', 'preparar_eliminacion_reserva'], true)) {
                        $this->functions->validarPrepararPago($args, $previousReservations);
                    } elseif ($call['name'] === 'iniciar_reserva') {
                        $this->functions->validarInicioReserva($args);
                    } elseif ($call['name'] === 'preparar_reserva') {
                        $this->functions->validarPrepararReserva($args, $previous);
                    } elseif ($call['name'] === 'listar_reservas_impagas') {
                        $this->functions->validarReservasImpagas($args);
                    } elseif ($call['name'] === 'listar_tarifas_cabana') {
                        $this->functions->validarListadoTarifas($args, $previous);
                    } elseif ($tariffs) {
                        $this->functions->validarTarifas($args, $previous);
                    } elseif ($details) {
                        $this->functions->validarDetalleReservas($args, $previousReservations);
                    } elseif ($payments) {
                        $this->functions->validarPagosReservas($args, $previousReservations);
                    } elseif ($call['name'] === 'consultar_disponibilidad') {
                        $this->functions->validarDisponibilidad($args);
                    } else {
                        $this->functions->validar($args);
                    }
                    // Also revalidates session owner/revision/expiration before querying.
                    $identity = $freshIdentity();
                    if ($call['name'] === 'buscar_pasajeros') {
                        $data = $this->functions->buscarPasajeros($identity, $args);
                    } elseif ($call['name'] === 'preparar_edicion_pasajero') {
                        $data = $this->functions->prepararEdicionPasajero($identity, $args, $previousReservations, $previousPassengers);
                    } elseif ($call['name'] === 'consultar_ultima_reserva') {
                        $data = $this->functions->consultarUltimaReserva($identity, $args);
                    } elseif ($call['name'] === 'listar_reservas_por_titular') {
                        $data = $this->functions->listarReservasPorTitular($identity, $args);
                    } elseif ($call['name'] === 'preparar_eliminacion_reserva') {
                        $data = $this->functions->prepararEliminacionReserva($identity, $args, $previousReservations);
                    } elseif ($call['name'] === 'preparar_eliminacion_pago_reserva') {
                        $data = $this->functions->prepararEliminacionPagoReserva($identity, $args, $previousReservations);
                    } elseif ($call['name'] === 'preparar_pago_reserva') {
                        $data = $this->functions->prepararPagoReserva($identity, $args, $previousReservations);
                    } elseif ($call['name'] === 'iniciar_reserva') {
                        $data = $this->functions->iniciarReserva($identity, $args);
                        if (isset($data['cabanas'])) {
                            $flow = ['etapa' => 'seleccion', 'desde' => $data['desde'], 'hasta' => $data['hasta']];
                        } else {
                            $flow = $flow ?: ['etapa' => 'periodo'];
                            if (isset($flow['dia_desde'])) { $data['text'] = FlujoReserva::pregunta($flow); }
                        }
                    } elseif ($call['name'] === 'preparar_reserva') {
                        $data = $this->functions->prepararReserva($identity, $args, $previous);
                    } elseif ($call['name'] === 'listar_reservas_impagas') {
                        $data = $this->functions->listarReservasImpagas($identity, $args);
                    } elseif ($call['name'] === 'listar_tarifas_cabana') {
                        $data = $this->functions->listarTarifasCabana($identity, $args, $previous);
                    } elseif ($tariffs) {
                        $data = $this->functions->consultarTarifas($identity, $args, $previous);
                    } elseif ($details) {
                        $data = $this->functions->consultarDetalleReservas($identity, $args, $previousReservations);
                    } elseif ($payments) {
                        $data = $this->functions->consultarPagosReservas($identity, $args, $previousReservations);
                    } elseif ($call['name'] === 'listar_reservas_registradas') {
                        $data = $this->functions->listarReservasRegistradas($identity, $args);
                    } elseif ($call['name'] === 'listar_reservas_por_estadia') {
                        $data = $this->functions->listarReservasPorEstadia($identity, $args);
                    } elseif ($call['name'] === 'listar_reservas_por_ingreso') {
                        $data = $this->functions->listarReservasPorIngreso($identity, $args);
                    } else {
                        $data = $this->functions->consultarDisponibilidad($identity, $args);
                    }
                    if ($flow && isset($data['cabanas'], $data['desde'], $data['hasta'])
                        && in_array($call['name'], ['consultar_disponibilidad', 'consultar_tarifas'], true)) {
                        $flow = ['etapa' => 'seleccion', 'desde' => $data['desde'], 'hasta' => $data['hasta']];
                    }
                    if (in_array($call['name'], ['buscar_pasajeros', 'consultar_ultima_reserva', 'listar_reservas_registradas', 'listar_reservas_por_estadia',
                        'listar_reservas_por_ingreso', 'listar_reservas_impagas', 'consultar_detalle_reservas',
                        'consultar_pagos_reservas', 'listar_tarifas_cabana'], true) && $flow) {
                        $flow = ['etapa' => 'cerrado'];
                    }
                    if ($call['name'] === 'listar_reservas_por_titular' && ($paymentFlow['etapa'] ?? '') === 'reserva') {
                        $paymentFlow['titular'] = $args['titular'];
                        $data['visible_presentation']['notes'][] = ($paymentFlow['operacion'] ?? '') === 'eliminar'
                            ? 'Para continuar con la eliminación del pago, indicá el número de una reserva.'
                            : 'Para continuar con la carga del pago, indicá el número de una reserva.';
                    }
                    $code = 'ok';
                    $reply = $data['visible_text'] ?? $data['text'];
                    $internalReply = $data['text'];
                    $presentation = $data['visible_presentation'] ?? null;
                    $passenger = $data['visible_passenger'] ?? null;
                    unset($data['visible_passenger']);
                    $reservationDeletion = $data['visible_reservation_deletion'] ?? null;
                    unset($data['visible_reservation_deletion']);
                    $deletion = $data['visible_deletion'] ?? null;
                    unset($data['visible_deletion']);
                    $payment = $data['visible_payment'] ?? null;
                    if ($payment !== null && FlujoPago::precarga($text)) { $payment['precarga'] = FlujoPago::precarga($text); }
                    unset($data['visible_payment']);
                    $proposal = $data['visible_proposal'] ?? null;
                    unset($data['visible_proposal']);
                    unset($data['visible_presentation']); // Presentation belongs to the UI, not the provider's context.
                    unset($data['visible_text']); // Never send private text back to Gemini on a later turn.
                    $unit[] = ['role' => 'assistant', 'text' => '', 'tool_calls' => $calls,
                        'provider_state' => $result['provider_state']];
                    $unit[] = ['role' => 'tool', 'name' => $call['name'], 'tool_call_id' => $call['id'], 'data' => $data];
                } catch (\InvalidArgumentException $e) {
                    $code = 'invalid_arguments';
                    $reply = $call['name'] === 'listar_reservas_impagas'
                        ? 'Podés pedir reservas impagas por titular, indicando su nombre (2 a 100 caracteres), sin números ni fechas. Si indicás un período, enviá fechas reales de inicio y fin y el criterio de fechas.'
                        : ($call['name'] === 'listar_tarifas_cabana'
                        ? 'Indicá una cabaña existente (1 a 99) o todas las cabañas. Podés listar sus tarifas sin fechas o indicar un período de vigencia válido.'
                        : (in_array($call['name'], ['consultar_detalle_reservas', 'consultar_pagos_reservas'], true)
                        ? 'No pude identificar las reservas. Indicá sus números (hasta 20) o consultá primero el listado; alguna reserva puede no existir.'
                        : ($call['name'] === 'consultar_tarifas'
                        ? 'No pude identificar un período y una selección válidos para las tarifas. Indicá fechas reales con salida posterior al ingreso (mínimo un día) '
                            . 'y números de cabaña, o consultá disponibilidad antes de pedir los valores de las anteriores.'
                        : ($call['name'] === 'consultar_disponibilidad'
                            ? 'Indicá fechas reales con salida posterior al ingreso (mínimo un día) y, si corresponde, huéspedes por cabaña como entero positivo.'
                            : 'El período no es válido. Indicá fechas reales completas, con inicio anterior o igual al final.'))));
                    if (in_array($call['name'], ['iniciar_reserva', 'preparar_reserva'], true)) {
                        $reply = 'Para comenzar, indicá fechas reales de ingreso y salida con día, mes y año (mínimo un día). Te mostraré disponibilidad y precios para elegir las cabañas. Si ya elegiste, indicá sus números. Los datos del pasajero se completan en el formulario privado.';
                    }
                    if ($call['name'] === 'buscar_pasajeros') { $reply = 'Indicá un texto de 2 a 100 caracteres y el campo para buscar pasajeros. Para calificación, usá de 1 a 5 o 0 para sin calificar.'; }
                    if ($call['name'] === 'listar_reservas_por_titular') { $reply = 'Indicá el nombre del titular (2 a 100 caracteres) y, si corresponde, un período válido.'; }
                    if (in_array($call['name'], ['preparar_pago_reserva', 'preparar_eliminacion_pago_reserva', 'preparar_eliminacion_reserva'], true)) { $reply = 'Para operar sobre un pago, indicá una sola reserva por su número o referite a una única reserva anterior.'; }
                } catch (\DomainException $e) {
                    $code = 'query_error';
                    $reply = in_array($call['name'], ['preparar_pago_reserva', 'preparar_eliminacion_pago_reserva', 'preparar_eliminacion_reserva'], true) ? $e->getMessage() : 'No se puede preparar la reserva: alguna cabaña ya no está disponible o no tiene tarifa completa. Consultá nuevamente disponibilidad y precios.';
                } catch (\yii\web\HttpException $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    $code = 'query_error';
                    $reply = 'No se pudo completar la consulta. No se obtuvieron resultados; intentá más tarde.';
                }
            }
        } elseif (($result['code'] ?? '') === 'provider_rate_limit') {
            $code = 'provider_rate_limit';
            $reply = $this->providerLabel . ' rechazó la consulta por un límite de uso o cuota (429). Esperá antes de volver a intentar. '
                . ($this->metadata['provider'] === 'openai' ? 'Si persiste, revisá los límites y la facturación en la plataforma de OpenAI. '
                    : 'Si persiste, revisá la cuota del proyecto en Google AI Studio. ') . 'No se pudo ejecutar la consulta de reservas.';
        } elseif (($result['code'] ?? '') === 'provider_unavailable') {
            $code = 'provider_unavailable';
            $reply = $this->providerLabel . ' no está disponible temporalmente. No se pudo ejecutar la consulta. '
                . 'Esperá unos momentos y volvé a enviar el mensaje en esta conversación.';
        } elseif (($result['code'] ?? '') === 'provider_timeout') {
            $code = 'provider_timeout';
            $reply = 'Se agotó el tiempo de espera de ' . $this->providerLabel . '. No se obtuvieron resultados. '
                . 'Podés volver a enviar el mensaje en esta conversación.';
        } elseif (($result['code'] ?? '') === 'provider_configuration') {
            $code = 'provider_configuration';
            $reply = 'La configuración o autorización de ' . $this->providerLabel . ' no permite completar la consulta. Revisá la configuración privada del proveedor.';
        } elseif (($result['code'] ?? '') === 'invalid_response') {
            $code = 'invalid_response';
        }
        $unit[] = ['role' => 'assistant', 'text' => $internalReply ?? $reply];
        $turn = ['messages' => $unit, 'visible' => [$user, ['role' => 'assistant', 'text' => $reply]], 'code' => $code];
        if (is_array($presentation)) { $turn['visible'][1]['presentation'] = $presentation; }
        // Reject an oversized COMPLETE exchange rather than silently dropping its signatures/results.
        $bytes = max(strlen(serialize($turn)),
            strlen(json_encode($turn, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)));
        if ($bytes + 1024 > $this->maxBytes) {
            Diagnostico::registrar('context_limit', $started, $result['usage'] ?? null, $this->metadata);
            throw new \yii\web\HttpException(413, 'context_limit');
        }
        Diagnostico::registrar($code, $started, $result['usage'] ?? null, $this->metadata);
        if ($proposal !== null) {
            $turn['_proposal'] = $proposal;
            $flow = ['etapa' => 'confirmacion'];
        }
        if ($payment !== null) {
            $turn['_payment'] = $payment;
            $turn['reserva_pago'] = $payment['id_reserva'];
            $paymentFlow = ['etapa' => 'confirmacion'];
            $flow = ['etapa' => 'cerrado'];
        }
        if ($deletion !== null) {
            $turn['_deletion'] = $deletion;
            $turn['reserva_pago'] = $deletion['id_reserva'];
            $paymentFlow = ['etapa' => 'confirmacion', 'operacion' => 'eliminar'];
            $flow = ['etapa' => 'cerrado'];
        }
        if ($reservationDeletion !== null) {
            $turn['_reservation_deletion'] = $reservationDeletion;
            $reservationDeletionFlow = ['etapa' => 'confirmacion'];
            $paymentFlow = ['etapa' => 'cerrado']; $flow = ['etapa' => 'cerrado'];
        }
        if ($passenger !== null) {
            $turn['_passenger'] = $passenger;
            $flow = ['etapa' => 'cerrado']; $paymentFlow = ['etapa' => 'cerrado'];
            $reservationDeletionFlow = ['etapa' => 'cerrado'];
        }
        if ($reservationDeletionFlow) { $turn['flujo_eliminar_reserva'] = $reservationDeletionFlow; }
        if ($flow) { $turn['flujo_reserva'] = $flow; }
        if ($paymentFlow) { $turn['flujo_pago'] = $paymentFlow; }
        return $turn;
    }
}
