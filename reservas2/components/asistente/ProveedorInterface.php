<?php
namespace app\components\asistente;

interface ProveedorInterface
{
    /** Common messages: role, text, tool_calls, tool_call_id, data, provider_state (opaque).
     * Returns: ok, code, tool_calls, provider_state and usage (null means unknown).
     * Never executes tools or returns user-visible factual prose.
     */
    public function interpretar(array $messages, \DateTimeImmutable $now);
}
