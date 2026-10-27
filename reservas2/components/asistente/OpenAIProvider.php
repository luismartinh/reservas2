<?php
namespace app\components\asistente;

/** Direct HTTPS Responses API; no SDK dependency, remote conversation or hosted tools. */
class OpenAIProvider extends \yii\base\Component implements ProveedorInterface
{
    const ENDPOINT = 'https://api.openai.com/v1/responses';
    public $model = 'gpt-5.4-mini';
    public $apiKeyFile = EntornoPrivado::ARCHIVO;
    public $connectTimeout = 5;
    public $requestTimeout = 60;

    public function interpretar(array $messages, \DateTimeImmutable $now)
    {
        try {
            $input = [];
            foreach ($messages as $message) {
                if (isset($message['provider_state']['gemini'])) { return $this->fallo('invalid_response'); }
                if (isset($message['provider_state']['openai'])) {
                    $state = $message['provider_state']['openai'];
                    if (($state['model'] ?? '') !== $this->model || !is_array($state['output'] ?? null)) {
                        return $this->fallo('invalid_response');
                    }
                    $input = array_merge($input, $state['output']);
                } elseif ($message['role'] === 'tool') {
                    $output = json_encode($message['data'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    if ($output === false) { return $this->fallo('invalid_response'); }
                    $input[] = ['type' => 'function_call_output', 'call_id' => $message['tool_call_id'], 'output' => $output];
                } else {
                    $input[] = ['role' => $message['role'] === 'assistant' ? 'assistant' : 'user', 'content' => $message['text']];
                }
            }
            $tools = [];
            foreach (ContratoAsistente::funciones() as $function) {
                // Optional arguments remain optional. Yii validates every argument before querying.
                $tools[] = ['type' => 'function', 'name' => $function['name'], 'description' => $function['description'],
                    'parameters' => $function['parameters'], 'strict' => false];
            }
            $response = $this->enviarSolicitud(['model' => $this->model,
                'instructions' => ContratoAsistente::instrucciones($now), 'input' => $input,
                'tools' => $tools, 'tool_choice' => 'auto', 'parallel_tool_calls' => false,
                'store' => false, 'include' => ['reasoning.encrypted_content'], 'max_output_tokens' => 2048]);
            if (empty($response['ok'])) { return $this->fallo($response['code'] ?? 'provider_error'); }
            return $this->normalizar($response['data']);
        } catch (\Throwable $e) { return $this->fallo('provider_error'); }
    }

    private function fallo($code, $usage = null)
    {
        $allowed = ['provider_error', 'provider_configuration', 'provider_rate_limit', 'provider_unavailable', 'provider_timeout', 'invalid_response'];
        return ['ok' => false, 'code' => in_array($code, $allowed, true) ? $code : 'provider_error', 'usage' => $usage];
    }

    protected function enviarSolicitud(array $payload)
    {
        $key = EntornoPrivado::valor('OPENAI_API_KEY', $this->apiKeyFile);
        if (!is_string($key) || trim($key) === '' || preg_match('/[\x00-\x20\x7f]/', $key)) {
            return $this->fallo('provider_configuration');
        }
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($body === false) { return $this->fallo('provider_error'); }
        $ch = null;
        try {
            $ch = curl_init(self::ENDPOINT);
            if ($ch === false) { return $this->fallo('provider_error'); }
            $timeout = max(1, min(60, (int) $this->requestTimeout));
            if (!curl_setopt_array($ch, [CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
                CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => max(1, min(5, $timeout, (int) $this->connectTimeout)), CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2])) {
                return $this->fallo('provider_error');
            }
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = (int) curl_errno($ch);
            // Numeric transport metadata only. Error bodies can echo keys or prompts and are discarded.
            Diagnostico::transporteOpenAI($status, $errno);
            if ($raw === false || $errno !== 0) { return $this->fallo($errno === 28 ? 'provider_timeout' : 'provider_error'); }
            if ($status === 429) { return $this->fallo('provider_rate_limit'); }
            if ($status === 401 || $status === 403) { return $this->fallo('provider_configuration'); }
            if ($status === 408 || $status === 504) { return $this->fallo('provider_timeout'); }
            if ($status >= 500 && $status <= 599) { return $this->fallo('provider_unavailable'); }
            if ($status < 200 || $status >= 300) { return $this->fallo('provider_error'); }
            $data = json_decode($raw, true);
            if (!is_array($data) || isset($data['error'])) { return $this->fallo('invalid_response'); }
            return ['ok' => true, 'data' => $data];
        } catch (\Throwable $e) { return $this->fallo('provider_error'); }
        finally { if ($ch !== null && $ch !== false) { curl_close($ch); } }
    }

    public function normalizar(array $response)
    {
        $usage = isset($response['usage']['total_tokens']) && is_int($response['usage']['total_tokens']) && $response['usage']['total_tokens'] >= 0
            ? $response['usage']['total_tokens'] : null;
        if (($response['object'] ?? '') !== 'response' || ($response['status'] ?? '') !== 'completed'
            || empty($response['output']) || !is_array($response['output'])) {
            return $this->fallo('invalid_response', $usage);
        }
        $calls = [];
        $ids = [];
        foreach ($response['output'] as $item) {
            if (!is_array($item)) { return $this->fallo('invalid_response', $usage); }
            if (($item['type'] ?? '') === 'function_call') {
                if (!is_string($item['call_id'] ?? null) || $item['call_id'] === '' || isset($ids[$item['call_id']])
                    || !is_string($item['name'] ?? null) || $item['name'] === '' || !is_string($item['arguments'] ?? null)) {
                    return $this->fallo('invalid_response', $usage);
                }
                $object = json_decode($item['arguments']);
                $arguments = json_decode($item['arguments'], true);
                if (!$object instanceof \stdClass || !is_array($arguments)) { return $this->fallo('invalid_response', $usage); }
                $ids[$item['call_id']] = true;
                $calls[] = ['id' => $item['call_id'], 'name' => $item['name'], 'arguments' => $arguments];
            } elseif (($item['type'] ?? '') === 'reasoning') {
                if (!is_string($item['encrypted_content'] ?? null) || $item['encrypted_content'] === '') {
                    return $this->fallo('invalid_response', $usage);
                }
            } elseif (($item['type'] ?? '') === 'message') {
                if (($item['role'] ?? '') !== 'assistant' || !is_array($item['content'] ?? null)) {
                    return $this->fallo('invalid_response', $usage);
                }
                foreach ($item['content'] as $part) {
                    if (!is_array($part) || !in_array($part['type'] ?? '', ['output_text', 'refusal'], true)
                        || !is_string($part[($part['type'] ?? '') === 'refusal' ? 'refusal' : 'text'] ?? null)) {
                        return $this->fallo('invalid_response', $usage);
                    }
                }
            } else { return $this->fallo('invalid_response', $usage); }
        }
        return ['ok' => true, 'code' => 'ok', 'tool_calls' => $calls,
            'provider_state' => ['openai' => ['model' => $this->model, 'output' => $response['output']]], 'usage' => $usage];
    }
}
