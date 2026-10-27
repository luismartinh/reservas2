<?php
namespace app\components\asistente;

use yii\web\UploadedFile;
use yii\web\UnprocessableEntityHttpException;

/** Ephemeral multipart upload. No SDK, conversation, storage or content logging. */
class OpenAITranscriptor extends \yii\base\Component implements TranscriptorInterface
{
    const ENDPOINT = 'https://api.openai.com/v1/audio/transcriptions';
    public $model = 'gpt-transcribe';
    public $apiKeyFile = EntornoPrivado::ARCHIVO;

    public function transcribir($identity, UploadedFile $audio)
    {
        Acceso::exigir($identity);
        // Inspect actual bytes, not the client MIME, name, size or extension.
        $size = is_file($audio->tempName) ? filesize($audio->tempName) : 0;
        if ($audio->error !== UPLOAD_ERR_OK || !$size || $size > ConfiguracionAudio::MAX_BYTES) {
            throw new UnprocessableEntityHttpException('Grabá un audio de hasta 60 segundos y 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($audio->tempName);
        $formats = ['audio/webm' => 'webm', 'video/webm' => 'webm', 'audio/mp4' => 'mp4',
            'video/mp4' => 'mp4', 'audio/x-m4a' => 'm4a', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav'];
        if (!isset($formats[$mime])) {
            throw new UnprocessableEntityHttpException('Formato de audio no admitido. Usá la grabación del micrófono.');
        }
        $key = EntornoPrivado::valor('OPENAI_API_KEY', $this->apiKeyFile);
        if (!is_string($key) || trim($key) === '' || preg_match('/[\x00-\x20\x7f]/', $key)
            || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,99}$/D', $this->model)) {
            return ['ok' => false, 'code' => 'provider_configuration'];
        }
        $fields = ['model' => $this->model, 'file' => new \CURLFile($audio->tempName, $mime, 'consulta.' . $formats[$mime])];
        // gpt-transcribe uses plural languages; retain legacy model compatibility if explicitly configured.
        if ($this->model === 'gpt-transcribe' || strpos($this->model, 'gpt-transcribe-') === 0) {
            $fields['languages[]'] = 'es';
        } else { $fields['language'] = 'es'; }
        $ch = null;
        try {
            $ch = curl_init(self::ENDPOINT);
            if ($ch === false || !curl_setopt_array($ch, [CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key],
                CURLOPT_POSTFIELDS => $fields, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2])) {
                return ['ok' => false, 'code' => 'provider_error'];
            }
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = (int) curl_errno($ch);
            if ($raw === false || $errno) { return ['ok' => false, 'code' => $errno === 28 ? 'provider_timeout' : 'provider_error']; }
            if ($status === 429) { return ['ok' => false, 'code' => 'provider_rate_limit']; }
            if ($status === 401 || $status === 403) { return ['ok' => false, 'code' => 'provider_configuration']; }
            if ($status >= 500) { return ['ok' => false, 'code' => 'provider_unavailable']; }
            if ($status < 200 || $status >= 300) { return ['ok' => false, 'code' => 'provider_error']; }
            $data = json_decode($raw, true);
            if (!is_array($data) || isset($data['error']) || !is_string($data['text'] ?? null)
                || !mb_check_encoding($data['text'], 'UTF-8') || trim($data['text']) === '') {
                return ['ok' => false, 'code' => 'invalid_response'];
            }
            $text = trim($data['text']);
            if (mb_strlen($text) > 2000) { return ['ok' => false, 'code' => 'transcript_too_long']; }
            return ['ok' => true, 'texto' => $text];
        } catch (\Throwable $e) { return ['ok' => false, 'code' => 'provider_error'];
        } finally { if ($ch !== null && $ch !== false) { curl_close($ch); } }
    }
}
