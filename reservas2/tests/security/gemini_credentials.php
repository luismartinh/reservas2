<?php

// Standalone regression checks. All cURL calls are replaced locally: no network.
namespace app\components {
    class GeminiTransportFixture
    {
        public static $calls = 0;
        public static $closed = 0;
        public static $options = [];
        public static $status = 200;
        public static $errno = 0;
        public static $body;
        public static $throws = false;
    }

    function curl_init($url)
    {
        GeminiTransportFixture::$calls++;
        if ($url !== GeminiClient::ENDPOINT || strpos($url, '?') !== false) {
            throw new \RuntimeException('Unexpected endpoint');
        }
        return new \stdClass();
    }
    function curl_setopt_array($ch, $options)
    {
        GeminiTransportFixture::$options = $options;
        return true;
    }
    function curl_exec($ch)
    {
        if (GeminiTransportFixture::$throws) {
            throw new \RuntimeException('credential-canary private-user-data');
        }
        return GeminiTransportFixture::$body;
    }
    function curl_getinfo($ch, $option) { return GeminiTransportFixture::$status; }
    function curl_errno($ch) { return GeminiTransportFixture::$errno; }
    function curl_close($ch) { GeminiTransportFixture::$closed++; }
}

namespace {
    require dirname(__DIR__, 2) . '/vendor/autoload.php';
    require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';

    use app\components\GeminiTransportFixture as Transport;

    class GeminiContextFixture extends \yii\base\Component
    {
        public function createCommand($sql)
        {
            return new class {
                public function queryAll() { return [['usuario' => 'private-user-data']]; }
            };
        }
    }

    class GeminiDiagnosticUserFixture extends \yii\base\Component
    {
        public $isGuest = true;
        public $identity;
    }

    function check($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    $appPath = dirname(__DIR__, 2);
    $runtime = sys_get_temp_dir() . '/gemini-security-' . bin2hex(random_bytes(8));
    mkdir($runtime, 0700);
    new \yii\console\Application([
        'id' => 'gemini-security-checks',
        'basePath' => $appPath,
        'runtimePath' => $runtime,
        'components' => ['db' => new GeminiContextFixture()],
    ]);
    $previousKey = getenv('GEMINI_API_KEY');
    $debugBefore = [];
    foreach (glob($appPath . '/components/debug_*.json') as $path) {
        $debugBefore[$path] = hash_file('sha256', $path);
    }

    try {
        check(\app\components\GeminiClient::ENDPOINT === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent', 'Use the supported replacement for retired Gemini 2.0');
        $privateFile = $runtime . '/reservas.env';
        $clients = [
            new \app\components\OpenAIClientGemini(['apiKeyFile' => $privateFile]),
            new \app\components\OpenAIClientGeminiSql(['apiKeyFile' => $privateFile]),
        ];
        $user = (object) ['id' => 1];
        foreach ($clients as $client) {
            foreach ([false, '', '   ', "bad\r\nheader"] as $missing) {
                putenv($missing === false ? 'GEMINI_API_KEY' : 'GEMINI_API_KEY=' . $missing);
                $before = Transport::$calls;
                $result = $client->askQuestion('private-question', null);
                check($result['st'] === 'error', 'Missing/invalid key must fail safely');
                check(Transport::$calls === $before, 'Invalid key must not reach HTTP');
                check(strpos($result['msg'], 'no está configurado') !== false, 'Controlled configuration message');
            }
            putenv('GEMINI_API_KEY=credential-canary');
            Transport::$status = 200;
            Transport::$errno = 0;
            Transport::$body = json_encode(['candidates' => [['content' => ['parts' => [['text' => "```sql\nSELECT 1\n```"]]]]]]);
            $result = $client->askQuestion('private-question', $user);
            check($result['st'] === 'ok', 'Successful response must retain its contract');
            check(strpos($result['response'], 'SELECT 1') !== false, 'Generated text preserved');
            if ($client instanceof \app\components\OpenAIClientGeminiSql) {
                check(trim($result['response']) === 'SELECT 1', 'SQL fences removed');
            } else {
                check(strpos($result['response'], '<br') !== false, 'HTML line breaks preserved');
            }
            check(in_array('x-goog-api-key: credential-canary', Transport::$options[CURLOPT_HTTPHEADER], true), 'Key must be in header');
            check(Transport::$options[CURLOPT_SSL_VERIFYPEER] === true, 'TLS verification enabled');
            check(Transport::$options[CURLOPT_FOLLOWLOCATION] === false, 'Credential redirects disabled');
            $payload = json_decode(Transport::$options[CURLOPT_POSTFIELDS], true);
            check($payload['generationConfig']['maxOutputTokens'] === 8192, 'Output limit preserved');
            check($payload['generationConfig']['responseMimeType'] === 'text/plain', 'Text output preserved');
            foreach (['temperature', 'topK', 'topP'] as $obsolete) {
                check(!array_key_exists($obsolete, $payload['generationConfig']), 'Do not send deprecated sampling parameters to Gemini 3.6');
            }

            Transport::$status = 403;
            Transport::$body = json_encode(['error' => ['message' => 'credential-canary private-user-data']]);
            $result = $client->askQuestion('private-question', $user);
            check($result['st'] === 'error', 'HTTP error handled');
            check(strpos(json_encode($result), 'credential-canary') === false, 'HTTP error must not echo credentials');

            Transport::$status = 200;
            Transport::$body = 'invalid-json credential-canary';
            check($client->askQuestion('private-question', $user)['st'] === 'error', 'Malformed JSON handled');
            Transport::$body = false;
            Transport::$errno = 7;
            check($client->askQuestion('private-question', $user)['st'] === 'error', 'Transport failure handled');
            Transport::$errno = 0;
            Transport::$throws = true;
            check($client->askQuestion('private-question', $user)['st'] === 'error', 'Exception handled');
            Transport::$throws = false;
            check($client->askQuestion('private-question', null)['st'] === 'error', 'Context exception handled');
        }

        // Hosting through FTP: only the private file supplies the credential.
        Transport::$status = 200;
        Transport::$errno = 0;
        Transport::$body = json_encode(['candidates' => [['content' => ['parts' => [['text' => 'SELECT 1']]]]]]);
        foreach ($clients as $client) {
            foreach ([
                "# private file\nGEMINI_API_KEY=credential-canary\nOTHER_SETTING=ignored\n",
                "\xEF\xBB\xBF" . "GEMINI_API_KEY=\"credential-canary\"\r\n",
                "GEMINI_API_KEY='credential-canary'\n",
            ] as $contents) {
                putenv('GEMINI_API_KEY');
                file_put_contents($privateFile, $contents);
                check($client->askQuestion('private-question', $user)['st'] === 'ok', 'Private file credential loaded');
                check(in_array('x-goog-api-key: credential-canary', Transport::$options[CURLOPT_HTTPHEADER], true), 'File credential sent only in header');
                check(getenv('GEMINI_API_KEY') === false && getenv('OTHER_SETTING') === false, 'Private file must not populate process environment');
                $before = Transport::$calls;
                $status = $client->checkConfiguration();
                check($status === [
                    'archivo_existe' => true,
                    'archivo_legible' => true,
                    'lectura_correcta' => true,
                    'clave_archivo_valida' => true,
                    'origen_clave' => 'archivo',
                    'configuracion_valida' => true,
                ], 'Private file diagnostic must confirm real reading and valid configuration');
                check(Transport::$calls === $before, 'Diagnostic must not call HTTP');
            }
            file_put_contents($privateFile, "GEMINI_API_KEY=rotated-canary\n");
            check($client->askQuestion('private-question', $user)['st'] === 'ok', 'File rotation loaded without restart');
            check(in_array('x-goog-api-key: rotated-canary', Transport::$options[CURLOPT_HTTPHEADER], true), 'Rotated file value used');
            putenv('GEMINI_API_KEY=environment-canary');
            check($client->askQuestion('private-question', $user)['st'] === 'ok', 'Environment still supported');
            check(in_array('x-goog-api-key: environment-canary', Transport::$options[CURLOPT_HTTPHEADER], true), 'Environment takes precedence');
            putenv('GEMINI_API_KEY=');
            $before = Transport::$calls;
            check($client->askQuestion('private-question', $user)['st'] === 'error', 'Invalid existing environment does not fall back');
            check(Transport::$calls === $before, 'Invalid environment must not reach HTTP');

            putenv('GEMINI_API_KEY');
            foreach ([
                '', "UNRELATED=value\n", "GEMINI_API_KEY=\n",
                "GEMINI_API_KEY=credential-canary\nGEMINI_API_KEY=duplicate\n",
                "GEMINI_API_KEY=\"unclosed\n", 'GEMINI_API_KEY=${OTHER_SETTING}',
                "GEMINI_API_KEY=bad value\n", str_repeat('x', 16385),
            ] as $invalid) {
                file_put_contents($privateFile, $invalid);
                $before = Transport::$calls;
                $result = $client->askQuestion('private-question', $user);
                check($result['st'] === 'error', 'Invalid private file must fail safely');
                check(strpos($result['msg'], $privateFile) === false, 'Private path must not be exposed');
                check(Transport::$calls === $before, 'Invalid private file must not reach HTTP');
                $status = $client->checkConfiguration();
                check($status['lectura_correcta'] === true && $status['clave_archivo_valida'] === false, 'Diagnostic must distinguish readable file from invalid credential');
            }
            unlink($privateFile);
            check($client->askQuestion('private-question', $user)['st'] === 'error', 'Missing private file handled');
            $status = $client->checkConfiguration();
            check($status['archivo_existe'] === false && $status['lectura_correcta'] === false, 'Missing file diagnostic handled');
        }

        $diagnosticUser = new GeminiDiagnosticUserFixture();
        Yii::$app->set('user', $diagnosticUser);
        Yii::$app->set('response', new \yii\web\Response());
        Yii::$app->urlManager->scriptUrl = '/index.php';
        $controller = new \app\controllers\AgentController('agent', Yii::$app);
        Yii::$app->controller = $controller;
        foreach ([null, (object) ['nivel' => 5], (object) ['nivel' => 2]] as $identity) {
            $diagnosticUser->identity = $identity;
            $diagnosticUser->isGuest = $identity === null;
            $denied = false;
            try {
                $controller->actionCheckGeminiConfig();
            } catch (\yii\web\ForbiddenHttpException $e) {
                $denied = true;
            }
            check($denied, 'Guest and non-admin diagnostic access denied');
        }
        foreach ([0, 1] as $level) {
            $diagnosticUser->identity = (object) ['nivel' => $level];
            $diagnosticUser->isGuest = false;
            $before = Transport::$calls;
            $response = $controller->actionCheckGeminiConfig();
            check($response->format === \yii\web\Response::FORMAT_JSON, 'Diagnostic response is JSON');
            check($response->headers->get('Cache-Control') === 'no-store', 'Diagnostic must not be cached');
            check(Transport::$calls === $before, 'Admin diagnostic makes no HTTP requests');
            check(strpos(json_encode($response->data), 'credential-canary') === false, 'Diagnostic must not disclose credentials');
            check(strpos(json_encode($response->data), '/home/') === false, 'Diagnostic must not disclose private paths');
        }

        check(Transport::$calls === Transport::$closed, 'All transport handles closed');
        $diagnostics = file_get_contents($runtime . '/gemini-diagnostics.log');
        foreach (['credential-canary', 'rotated-canary', 'environment-canary', $privateFile, 'private-user-data', 'private-question', 'SELECT 1'] as $sensitive) {
            check(strpos($diagnostics, $sensitive) === false, 'Sensitive content in diagnostics');
        }
        foreach (explode("\n", trim($diagnostics)) as $entry) {
            $entry = json_decode($entry, true);
            check(array_keys($entry) === ['event', 'http_status', 'curl_errno'], 'Diagnostic fields must be allowlisted');
            check(is_int($entry['http_status']) && is_int($entry['curl_errno']), 'Numeric diagnostic codes');
        }
        foreach ($debugBefore as $path => $hash) {
            check(hash_file('sha256', $path) === $hash, 'Legacy debug file was modified');
        }
        echo "PASS: both clients, private file reading diagnostics, administrator-only access, no diagnostic HTTP calls, environment/file loading and priority, rotation, invalid configuration, header, success formatting, failure handling, safe logs and unchanged debug files.\n";
    } finally {
        putenv($previousKey === false ? 'GEMINI_API_KEY' : 'GEMINI_API_KEY=' . $previousKey);
        foreach (glob($runtime . '/*') as $path) {
            unlink($path);
        }
        rmdir($runtime);
    }
}
