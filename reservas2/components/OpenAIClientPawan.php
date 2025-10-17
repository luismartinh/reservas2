<?php

namespace app\components;

use Yii;
use yii\base\Component;

class OpenAIClientPawan extends Component
{
    private $apiKey;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->apiKey ="pk-ftCValYEUOzBaqgSudnJRijRUefjgrIOoAdwtFYyhvqfmRgT";
        
    }

    /**
     * Sends a question to the OpenAI API using the specified endpoint and returns the response.
     *
     * This function constructs a prompt containing information about the Yii2 application
     * and the user's question, sends a request to the OpenAI API, and processes the response.
     * It handles errors that may occur during the cURL request and logs the API response
     * for debugging purposes.
     *
     * @param string $question The user's question to be sent to the OpenAI API.
     * @return array An associative array containing the status of the execution, a message,
     *               and the response content from the API if successful.
     */

    public function askQuestion($question)   {

        $url='https://api.pawan.krd/cosmosrp/v1/chat/completions';

        //solo es un proxy a la api de openai
        //$url = 'http://chatgpt:3040/v1/chat/completions';




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
            'temperature' => 1.2,
        ];
        
        $ch = curl_init($url);
        if (!$ch) {

            return [
                'st'=>'error',
                'msg'=>'Error: No se pudo inicializar cURL.'
            ];
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
            return [
                'st'=>'error',
                'msg'=>'Error en la solicitud cURL: ' . curl_error($ch)
            ];

        }

        if (curl_errno($ch)) {

            return [
                'st'=>'error',
                'msg'=>'Error en la conexión: ' . curl_error($ch)
            ];
        }
        
        curl_close($ch);

        $responseData = json_decode($response, true);

        file_put_contents(__DIR__ . '/debug_openai_pawan_last_response.json', json_encode($responseData, JSON_PRETTY_PRINT));

        if(isset($responseData['error'])) {
            return [
                'st'=>'error',
                'msg'=>'Error en la conexión: ' . $responseData['error']['message']
            ];
            
        }   

        return [
            'st'=>'ok',
            'msg'=>'ejecucion OK ',
            'response'=>$responseData['choices'][0]['message']['content']
        ];

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
