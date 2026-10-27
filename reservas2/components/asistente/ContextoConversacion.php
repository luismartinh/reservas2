<?php
namespace app\components\asistente;

use Yii;
use yii\web\ConflictHttpException;

class ContextoConversacion
{
    const KEY = '__asistente';
    private $session;
    private $config;

    public function __construct($session, array $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    public function nueva($userId, $now = null)
    {
        $state = ['id' => bin2hex(random_bytes(16)), 'owner' => (string) $userId,
            'proveedor' => ProveedorFactory::vinculacion($this->config),
            'updated' => $now === null ? time() : $now, 'revision' => 0, 'turns' => [], 'recortado' => false];
        $this->session->set(self::KEY, $state);
        return $state;
    }

    public function actual($userId)
    {
        $state = $this->session->get(self::KEY);
        if (!$this->vigente($state, $userId, time())) {
            return $this->nueva($userId);
        }
        return $state;
    }

    private function vigente($state, $userId, $now)
    {
        return is_array($state) && $state['owner'] === (string) $userId
            && ($state['proveedor'] ?? ProveedorFactory::vinculacion(['proveedor' => 'gemini'])) === ProveedorFactory::vinculacion($this->config)
            && $now - $state['updated'] < $this->config['ttl'];
    }

    public function exigir($userId, $id, $revision = null, $now = null)
    {
        $state = $this->session->get(self::KEY);
        if (!$this->vigente($state, $userId, $now === null ? time() : $now)
            || !is_string($id) || !hash_equals($state['id'], $id)
            || ($revision !== null && $state['revision'] !== $revision)) {
            throw new ConflictHttpException('La conversación cambió o venció. Abrí una nueva conversación.');
        }
        return $state;
    }

    public function guardar($userId, array $snapshot, array $turn, $now = null)
    {
        $now = $now === null ? time() : $now;
        $state = $this->exigir($userId, $snapshot['id'], $snapshot['revision'], $now);
        $state['turns'][] = $turn;
        $state['updated'] = $now;
        $state['revision']++;
        while (count($state['turns']) > $this->config['turnos']
            || max(strlen(serialize($state)),
                strlen(json_encode($state, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE))) > $this->config['bytes']) {
            if (!$state['turns']) {
                break;
            }
            array_shift($state['turns']); // A whole user/model/tool/factual unit; never orphan a call.
            $state['recortado'] = true;
        }
        $this->session->set(self::KEY, $state);
        return $state;
    }

    public function visible(array $state)
    {
        $messages = [];
        foreach ($state['turns'] as $turn) {
            foreach ($turn['visible'] as $message) {
                if ($message['role'] === 'assistant' && in_array($turn['code'] ?? '',
                    ['provider_error', 'provider_configuration', 'invalid_response', 'provider_rate_limit', 'provider_unavailable', 'provider_timeout', 'query_error'], true)) {
                    $message['error'] = true;
                }
                $messages[] = $message;
            }
        }
        $actions = [];
        $draft = $this->session->get('__asistente_pago');
        if (is_array($draft) && ($draft['owner'] ?? null) === ($state['owner'] ?? null)
            && ($draft['conversation'] ?? null) === $state['id'] && ($draft['expires'] ?? 0) > time()
            && empty($draft['completed']) && preg_match('/^[a-f0-9]{32}$/D', $draft['token'] ?? '')) {
            $actions['pago'] = ['token' => $draft['token'], 'reserva' => (int) $draft['proposal']['id_reserva']];
        }
        $deletion = $this->session->get('__asistente_eliminar_pago');
        if (is_array($deletion) && ($deletion['owner'] ?? null) === ($state['owner'] ?? null)
            && ($deletion['conversation'] ?? null) === $state['id'] && ($deletion['expires'] ?? 0) > time()
            && empty($deletion['completed']) && preg_match('/^[a-f0-9]{32}$/D', $deletion['token'] ?? '')) {
            $actions['pago'] = ['token' => $deletion['token'], 'reserva' => (int) $deletion['proposal']['id_reserva'], 'operacion' => 'eliminar'];
        }
        $reservationDeletion = $this->session->get('__asistente_eliminar_reserva');
        if (is_array($reservationDeletion) && ($reservationDeletion['owner'] ?? null) === ($state['owner'] ?? null)
            && ($reservationDeletion['conversation'] ?? null) === $state['id'] && ($reservationDeletion['expires'] ?? 0) > time()
            && empty($reservationDeletion['completed']) && preg_match('/^[a-f0-9]{32}$/D', $reservationDeletion['token'] ?? '')) {
            $actions = ['eliminar_reserva' => ['token' => $reservationDeletion['token'], 'reserva' => (int) $reservationDeletion['proposal']['id_reserva']]];
        }
        $passenger = $this->session->get('__asistente_pasajero');
        if (is_array($passenger) && ($passenger['owner'] ?? null) === $state['owner']
            && ($passenger['conversation'] ?? null) === $state['id'] && ($passenger['expires'] ?? 0) > time()
            && empty($passenger['completed']) && preg_match('/^[a-f0-9]{32}$/D', $passenger['token'] ?? '')) {
            $actions['pasajero'] = ['token' => $passenger['token'], 'operacion' => $passenger['proposal']['operacion']];
        }
        return ['conversacion' => $state['id'], 'mensajes' => $messages, 'recortado' => $state['recortado'], 'acciones' => $actions];
    }
}
