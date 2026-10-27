<?php

namespace app\components;

use Yii;
use yii\base\Component;

/** Shared configuration, transport and safe diagnostics for the Gemini clients. */
abstract class GeminiClient extends Component
{
    const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';

    /** Private file outside public_html for deployments managed through FTP. */
    public $apiKeyFile = '/home/vaqxtmgw/reservas.env';

    // Defaults preserved for existing clients. A subclass may use a shorter budget.
    public $connectTimeout = 10;
    public $requestTimeout = 60;

    /** Local checks only: no transport, response bodies or credential values. */
    public function checkConfiguration()
    {
        $status = [
            'archivo_existe' => false,
            'archivo_legible' => false,
            'lectura_correcta' => false,
            'clave_archivo_valida' => false,
            'origen_clave' => getenv('GEMINI_API_KEY') === false ? 'archivo' : 'entorno',
            'configuracion_valida' => false,
        ];
        try {
            if (is_string($this->apiKeyFile)) {
                $status['archivo_existe'] = @is_file($this->apiKeyFile);
                $status['archivo_legible'] = $status['archivo_existe'] && @is_readable($this->apiKeyFile);
                if ($status['archivo_legible']) {
                    $status['lectura_correcta'] = @file_get_contents($this->apiKeyFile, false, null, 0, 1) !== false;
                    $status['clave_archivo_valida'] = $this->isValidApiKey($this->readApiKeyFile());
                }
            }
            $status['configuracion_valida'] = $this->getApiKey() !== null;
        } catch (\Throwable $e) {
            // Failed filesystem checks expose only the false status fields.
        }
        return $status;
    }

    public function askQuestion($question, $user)
    {
        if ($this->getApiKey() === null) {
            $this->recordDiagnostic('configuration_error');
            return ['st' => 'error', 'msg' => 'El servicio Gemini no está configurado. Contacte al administrador.'];
        }

        try {
            return $this->askConfiguredQuestion($question, $user);
        } catch (\Throwable $e) {
            // Never log the exception: its message or arguments may contain secrets.
            $this->recordDiagnostic('internal_error');
            return $this->requestError();
        }
    }

    abstract protected function askConfiguredQuestion($question, $user);

    private function getApiKey()
    {
        $key = getenv('GEMINI_API_KEY');
        // An explicitly configured environment value takes precedence, even if invalid.
        if ($key === false) {
            $key = $this->readApiKeyFile();
        }
        if (!$this->isValidApiKey($key)) {
            return null;
        }
        return $key;
    }

    private function isValidApiKey($key)
    {
        return is_string($key) && trim($key) !== '' && !preg_match('/[\x00-\x20\x7f]/', $key);
    }

    private function readApiKeyFile()
    {
        try {
            if (!is_string($this->apiKeyFile) || !@is_file($this->apiKeyFile)
                || !@is_readable($this->apiKeyFile)) {
                return null;
            }
            // This is a data file, never executable PHP or a general environment loader.
            $contents = @file_get_contents($this->apiKeyFile, false, null, 0, 16385);
            if ($contents === false || strlen($contents) > 16384) {
                return null;
            }
            $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
            $key = null;
            $found = false;
            foreach (preg_split('/\r\n|\n|\r/', $contents) as $line) {
                if (!preg_match('/^\s*GEMINI_API_KEY\s*=\s*(.*?)\s*$/', $line, $match)) {
                    continue;
                }
                if ($found) {
                    return null;
                }
                $found = true;
                $key = $match[1];
                if (strlen($key) >= 2 && ($key[0] === '"' || $key[0] === "'")) {
                    if (substr($key, -1) !== $key[0]) {
                        return null;
                    }
                    $key = substr($key, 1, -1);
                }
                // No interpolation, escaping, inline comments or multiline secrets.
                if (strpbrk($key, "\"'`$#\\") !== false) {
                    return null;
                }
            }
            return $key;
        } catch (\Throwable $e) {
            // Do not expose filesystem paths, file contents or exception arguments.
            return null;
        }
    }

    protected function sendRequest(array $data)
    {
        $key = $this->getApiKey();
        if ($key === null) {
            return $this->requestError();
        }

        $payload = json_encode($data);
        if ($payload === false) {
            $this->recordDiagnostic('encoding_error');
            return $this->requestError();
        }

        $ch = null;
        try {
            $ch = curl_init(self::ENDPOINT);
            if ($ch === false) {
                $this->recordDiagnostic('transport_error');
                return $this->requestError();
            }
            $configured = curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $key,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_CONNECTTIMEOUT => (int) $this->connectTimeout,
                CURLOPT_TIMEOUT => (int) $this->requestTimeout,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            if (!$configured) {
                $this->recordDiagnostic('transport_error');
                return $this->requestError();
            }

            $response = curl_exec($ch);
            $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = (int) curl_errno($ch);
            if ($response === false || $curlErrno !== 0) {
                $this->recordDiagnostic('transport_error', $httpStatus, $curlErrno);
                return $this->requestError();
            }
            if ($httpStatus < 200 || $httpStatus >= 300) {
                // Discard error bodies, which can echo credentials and user data.
                $this->recordDiagnostic('http_error', $httpStatus);
                return $this->requestError();
            }

            $decoded = json_decode($response, true);
            if (!is_array($decoded) || isset($decoded['error'])) {
                $this->recordDiagnostic('invalid_response', $httpStatus);
                return $this->requestError();
            }
            $this->recordDiagnostic('request_completed', $httpStatus);
            return ['st' => 'ok', 'data' => $decoded];
        } catch (\Throwable $e) {
            $this->recordDiagnostic('transport_error');
            return $this->requestError();
        } finally {
            if ($ch !== null && $ch !== false) {
                curl_close($ch);
            }
        }
    }

    private function requestError()
    {
        return ['st' => 'error', 'msg' => 'No se pudo obtener una respuesta de Gemini. Intente nuevamente más tarde.'];
    }

    /** Only fixed event names and numeric codes may reach the diagnostic file. */
    protected function recordDiagnostic($event, $httpStatus = 0, $curlErrno = 0)
    {
        $allowed = [
            'configuration_error', 'internal_error', 'context_error', 'encoding_error',
            'transport_error', 'http_error', 'invalid_response', 'request_completed',
        ];
        if (!in_array($event, $allowed, true)) {
            return;
        }
        try {
            $path = Yii::getAlias('@runtime/gemini-diagnostics.log');
            $entry = json_encode([
                'event' => $event,
                'http_status' => (int) $httpStatus,
                'curl_errno' => (int) $curlErrno,
            ]);
            // Avoid Yii log context, which may include environment or session data.
            @file_put_contents($path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Diagnostics must not interrupt requests or expose internal errors.
        }
    }
}
