<?php
namespace tests\support;

require_once __DIR__ . '/OpenAIHttp.php';

use app\components\asistente\GeminiProvider;

// Codeception parses test files before constructing its Yii application.
\Yii::setAlias('@app', dirname(__DIR__, 2));

/** CLI tests must not create root-owned private files used by Apache. */
trait RuntimeAsistenteAislado
{
    private $runtimeOriginal;
    private $runtimePruebas;

    protected function iniciarRuntimeAsistente()
    {
        $this->runtimeOriginal = \Yii::$app->runtimePath;
        $this->runtimePruebas = sys_get_temp_dir() . '/reservas2-asistente-test-' . bin2hex(random_bytes(8));
        \yii\helpers\FileHelper::createDirectory($this->runtimePruebas, 0700);
        \Yii::$app->setRuntimePath($this->runtimePruebas);
    }

    protected function cerrarRuntimeAsistente()
    {
        \Yii::$app->setRuntimePath($this->runtimeOriginal);
        \yii\helpers\FileHelper::removeDirectory($this->runtimePruebas);
    }
}

/** In-memory HTTP replacement; no cURL and no real credential required. */
class GeminiAsistenteSimulado extends GeminiProvider
{
    public static $payloads = [];
    public static $response;
    public static $duringRequest;
    public static $httpFailure = 0;
    public static $curlFailure = 0;

    public function interpretar(array $messages, \DateTimeImmutable $now)
    {
        // Only bypass credential loading. Exercise real payload construction and normalization.
        return $this->askConfiguredQuestion(['messages' => $messages, 'now' => $now], null);
    }

    protected function sendRequest(array $data)
    {
        self::$payloads[] = $data;
        if (self::$httpFailure) { $this->recordDiagnostic('http_error', self::$httpFailure); }
        elseif (self::$curlFailure) { $this->recordDiagnostic('transport_error', 0, self::$curlFailure); }
        if (self::$duringRequest) {
            call_user_func(self::$duringRequest);
        }
        return self::$response;
    }

    public static function reset()
    {
        self::$payloads = [];
        self::$duringRequest = null;
        self::$httpFailure = 0;
        self::$curlFailure = 0;
        self::$response = ['st' => 'ok', 'data' => [
            'candidates' => [['finishReason' => 'STOP', 'content' => ['role' => 'model', 'parts' => [[
                'functionCall' => ['id' => 'call-fixture', 'name' => 'consultar_disponibilidad',
                    'args' => ['desde' => '2026-11-10', 'hasta' => '2026-11-15']],
                'thoughtSignature' => 'opaque-signature',
            ]]]]],
        ]];
    }
}

trait CreaDatosAsistente
{
    protected function usuarioAsistente($nivel = 1)
    {
        $suffix = bin2hex(random_bytes(5));
        $user = new \app\models\Identificador([
            'login' => 'asist_' . $suffix, 'nombre' => 'Asistente', 'apellido' => 'Test',
            'email' => $suffix . '@example.com', 'activo' => 1, 'nivel' => $nivel, 'codigo' => 'A' . $suffix,
            'pwd' => 'test-not-a-real-password',
        ]);
        $user->setPassword('test-not-a-real-password');
        $user->generateAuthKey();
        \PHPUnit\Framework\Assert::assertTrue($user->save(), implode(' | ', $user->getErrorSummary(true)));
        return $user;
    }
}
