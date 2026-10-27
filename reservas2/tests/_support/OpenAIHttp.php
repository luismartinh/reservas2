<?php
namespace tests\support {
    /** Test-only cURL replacement. No credentials or payloads ever leave this process. */
    class OpenAIHttp
    {
        public static $options = [];
        public static $requests = 0;
        public static $status = 200;
        public static $errno = 0;
        public static $raw;
        public static $closed = 0;
        public static $duringRequest;
        public static function reset()
        {
            self::$options = [];
            self::$requests = 0;
            self::$status = 200;
            self::$errno = 0;
            self::$closed = 0;
            self::$duringRequest = null;
            self::$raw = json_encode(['object' => 'response', 'status' => 'completed',
                'output' => [['type' => 'function_call', 'id' => 'fc-fixture', 'call_id' => 'call-openai',
                    'name' => 'consultar_disponibilidad', 'arguments' => '{"desde":"2086-11-10","hasta":"2086-11-15"}']],
                'usage' => ['total_tokens' => 123]]);
        }
    }
}
namespace app\components\asistente {
    function curl_init($url) { return (object) ['url' => $url]; }
    function curl_setopt_array($handle, array $options) { \tests\support\OpenAIHttp::$options[] = ['url' => $handle->url, 'options' => $options]; return true; }
    function curl_exec($handle) {
        \tests\support\OpenAIHttp::$requests++;
        if (\tests\support\OpenAIHttp::$duringRequest) { call_user_func(\tests\support\OpenAIHttp::$duringRequest); }
        return \tests\support\OpenAIHttp::$raw;
    }
    function curl_getinfo($handle, $option) { return \tests\support\OpenAIHttp::$status; }
    function curl_errno($handle) { return \tests\support\OpenAIHttp::$errno; }
    function curl_close($handle) { \tests\support\OpenAIHttp::$closed++; }
}
