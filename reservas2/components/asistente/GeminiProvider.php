<?php
namespace app\components\asistente;

use app\components\GeminiClient;

/** Subclass because sendRequest is protected; no duplicate key/HTTP implementation. */
class GeminiProvider extends GeminiClient implements ProveedorInterface
{
    private $httpFailure = 0;
    private $timedOut = false;

    /** Reuse the transport's numeric diagnostics without retaining response bodies. */
    protected function recordDiagnostic($event, $httpStatus = 0, $curlErrno = 0)
    {
        if ($event === 'http_error') { $this->httpFailure = (int) $httpStatus; }
        if ($event === 'transport_error' && (int) $curlErrno === 28) { $this->timedOut = true; }
        parent::recordDiagnostic($event, $httpStatus, $curlErrno);
    }

    public function interpretar(array $messages, \DateTimeImmutable $now)
    {
        return $this->askQuestion(['messages' => $messages, 'now' => $now], null);
    }

    protected function askConfiguredQuestion($question, $user)
    {
        $this->httpFailure = 0;
        $this->timedOut = false;
        $contents = [];
        foreach ($question['messages'] as $message) {
            if (isset($message['provider_state']['openai'])) { return ['ok' => false, 'code' => 'invalid_response', 'usage' => null]; }
            if (isset($message['provider_state']['gemini'])) {
                // Replay the COMPLETE content, including IDs and signatures on any part.
                $contents[] = $message['provider_state']['gemini'];
            } elseif ($message['role'] === 'tool') {
                $contents[] = ['role' => 'user', 'parts' => [['functionResponse' => [
                    'id' => $message['tool_call_id'], 'name' => $message['name'],
                    'response' => $message['data'],
                ]]]];
            } else {
                $contents[] = ['role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $message['text']]]];
            }
        }
        $instruction = ContratoAsistente::instrucciones($question['now']);
        $result = $this->sendRequest([
            'systemInstruction' => ['parts' => [['text' => $instruction]]],
            'contents' => $contents,
            'tools' => [['functionDeclarations' => ContratoAsistente::funcionesGemini()]],
            'toolConfig' => ['functionCallingConfig' => ['mode' => 'AUTO']],
            'generationConfig' => ['maxOutputTokens' => 2048, 'candidateCount' => 1],
        ]);
        if ($result['st'] !== 'ok') {
            $code = 'provider_error';
            if ($this->httpFailure === 429) { $code = 'provider_rate_limit'; }
            elseif ($this->httpFailure === 503) { $code = 'provider_unavailable'; }
            elseif ($this->timedOut) { $code = 'provider_timeout'; }
            return ['ok' => false, 'code' => $code, 'usage' => null];
        }
        return $this->normalizar($result['data']);
    }

    public function normalizar(array $response)
    {
        $usage = null;
        if (isset($response['usageMetadata']['totalTokenCount'])
            && is_int($response['usageMetadata']['totalTokenCount'])) {
            $usage = $response['usageMetadata']['totalTokenCount'];
        }
        $candidates = $response['candidates'] ?? [];
        if (count($candidates) !== 1 || ($candidates[0]['finishReason'] ?? '') !== 'STOP') {
            return ['ok' => false, 'code' => 'invalid_response', 'usage' => $usage];
        }
        $content = $candidates[0]['content'] ?? [];
        if (($content['role'] ?? '') !== 'model' || empty($content['parts'])
            || !is_array($content['parts'])) {
            return ['ok' => false, 'code' => 'invalid_response', 'usage' => $usage];
        }
        $calls = [];
        foreach ($content['parts'] as $part) {
            if (!is_array($part)) {
                return ['ok' => false, 'code' => 'invalid_response', 'usage' => $usage];
            }
            if (isset($part['functionCall'])) {
                $call = $part['functionCall'];
                if (!is_array($call) || !is_string($call['id'] ?? null) || $call['id'] === ''
                    || !is_string($call['name'] ?? null) || !is_array($call['args'] ?? null)) {
                    return ['ok' => false, 'code' => 'invalid_response', 'usage' => $usage];
                }
                $calls[] = ['id' => $call['id'], 'name' => $call['name'], 'arguments' => $call['args']];
            } elseif (!isset($part['text']) || !is_string($part['text'])) {
                return ['ok' => false, 'code' => 'invalid_response', 'usage' => $usage];
            }
        }
        return ['ok' => true, 'code' => 'ok', 'tool_calls' => $calls,
            'provider_state' => ['gemini' => $content], 'usage' => $usage];
    }
}
