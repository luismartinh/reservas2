<?php
namespace app\components\asistente;

use Yii;

class Diagnostico
{
    public static function registrar($code, $started, $usage = null, array $metadata = [])
    {
        $allowed = ['ok', 'clarification', 'invalid_response', 'provider_error', 'provider_configuration', 'provider_rate_limit',
            'provider_unavailable', 'provider_timeout', 'invalid_arguments',
            'unsupported_calls', 'query_error', 'internal_error', 'stale_conversation', 'forbidden', 'context_limit'];
        if (!in_array($code, $allowed, true)) {
            $code = 'internal_error';
        }
        $metadata = ProveedorFactory::metadata(['proveedor' => $metadata['provider'] ?? 'gemini',
            'openaiModelo' => $metadata['model'] ?? 'gpt-5.4-mini']);
        $data = $metadata + [
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'code' => $code, 'total_tokens' => is_int($usage) && $usage >= 0 ? $usage : null];
        // No Yii log context (environment/session/request bodies).
        @file_put_contents(Yii::getAlias('@runtime/asistente-diagnostics.log'), json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function transporteOpenAI($status, $errno)
    {
        @file_put_contents(Yii::getAlias('@runtime/openai-diagnostics.log'), json_encode([
            'http_status' => (int) $status, 'curl_errno' => (int) $errno,
        ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
