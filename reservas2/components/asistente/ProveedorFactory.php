<?php
namespace app\components\asistente;

use app\components\GeminiClient;
use Yii;

class ProveedorFactory
{
    public static function metadata(array $config)
    {
        $provider = $config['proveedor'] ?? 'gemini';
        if (!in_array($provider, ['gemini', 'openai'], true)) { throw new \InvalidArgumentException('invalid_provider'); }
        $model = $provider === 'openai' ? ($config['openaiModelo'] ?? 'gpt-5.4-mini')
            : explode(':', basename(parse_url(GeminiClient::ENDPOINT, PHP_URL_PATH)))[0];
        if (!is_string($model) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:-]{0,99}$/D', $model)) {
            throw new \InvalidArgumentException('invalid_model');
        }
        if ($provider === 'openai' && !preg_match('/^(?:gpt-|chatgpt-|o[1-9])/', $model)) {
            throw new \InvalidArgumentException('invalid_model');
        }
        return ['provider' => $provider, 'model' => $model];
    }

    public static function crear(array $config)
    {
        $metadata = self::metadata($config);
        $options = ['class' => $metadata['provider'] === 'openai' ? OpenAIProvider::class : GeminiProvider::class,
            'connectTimeout' => min(5, $config['timeout']), 'requestTimeout' => $config['timeout']];
        if ($metadata['provider'] === 'openai') { $options['model'] = $metadata['model']; }
        return Yii::createObject($options);
    }

    public static function vinculacion(array $config)
    {
        $metadata = self::metadata($config);
        return $metadata['provider'] . ':' . $metadata['model'];
    }
}
