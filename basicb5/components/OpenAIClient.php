<?php

namespace app\components;

use Yii;
use yii\base\Component;

class OpenAIClient extends Component
{
    private $apiKey;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->apiKey = 'OPENAI_API_KEY_ENV'; // Reemplaza con tu API Key de OpenAI

        
    }

    public function askQuestion($question)
    {
        $url = 'https://api.openai.com/v1/completions';

        // Crea el prompt de la pregunta
        $prompt = "Basado en la siguiente información sobre mi aplicación Yii2, responde las preguntas de los usuarios.\n\n";
        $prompt .= "Controladores: " . json_encode($this->getControllersData()) . "\n";
        $prompt .= "Modelos: " . json_encode($this->getModelsData()) . "\n";
        $prompt .= "Pregunta: " . $question;

        $data = [
            'model' => 'cosmosrp',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente experto en Yii2. Responde preguntas sobre la aplicación.'],
                ['role' => 'user', 'content' => $question]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7,
        ];
        
        $ch = curl_init($url);
        if (!$ch) {
            return 'Error: No se pudo inicializar cURL.';
        }
    


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if ($response === false) {
            return 'Error en la solicitud cURL: ' . curl_error($ch);
        }

        if (curl_errno($ch)) {
            return 'Error en la conexión: ' . curl_error($ch);
        }
        
        curl_close($ch);

        file_put_contents(__DIR__ . '/debug_openai_response.json', $response);

        $responseData = json_decode($response, true);
        
        //return json_encode($responseData, JSON_PRETTY_PRINT);  // Devuelve la respuesta completa en formato legible
        

        $responseData = json_decode($response, true);
        return $responseData['choices'][0]['text'] ?? 'No se pudo obtener una respuesta.';
    }

    // Método para obtener datos de controladores
    private function getControllersData()
    {
        // Aquí puedes cargar los datos de los controladores desde tu archivo JSON
        $filePath = Yii::getAlias('@app/data/controllers_data.json');
        return json_decode(file_get_contents($filePath), true);
    }

    // Método para obtener datos de modelos
    private function getModelsData()
    {
        // Aquí puedes cargar los datos de los modelos desde tu archivo JSON
        $filePath = Yii::getAlias('@app/data/models_data.json');
        return json_decode(file_get_contents($filePath), true);
    }
}
