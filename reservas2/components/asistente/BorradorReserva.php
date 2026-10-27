<?php
namespace app\components\asistente;

use yii\web\ConflictHttpException;

/** One private expiring draft per session. No customer data goes into provider context. */
class BorradorReserva
{
    const KEY = '__asistente_reserva';
    private $session;
    private $key;

    public function __construct($session, $key = self::KEY) { $this->session = $session; $this->key = $key; }

    public function crear($owner, $conversation, array $proposal)
    {
        $draft = ['token' => bin2hex(random_bytes(16)), 'owner' => (string) $owner,
            'conversation' => $conversation, 'expires' => time() + 900, 'proposal' => $proposal];
        $this->session->set($this->key, $draft);
        return $draft;
    }

    public function exigir($owner, $conversation, $token)
    {
        $draft = $this->session->get($this->key);
        if (!is_array($draft) || !is_string($token) || !hash_equals($draft['token'], $token)
            || $draft['owner'] !== (string) $owner || $draft['conversation'] !== $conversation
            || $draft['expires'] <= time()) {
            throw new ConflictHttpException('La propuesta venció o cambió. Pedí preparar la reserva nuevamente.');
        }
        return $draft;
    }

    public function guardar(array $draft) { $this->session->set($this->key, $draft); }
}
