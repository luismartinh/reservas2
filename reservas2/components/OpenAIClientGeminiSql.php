<?php

namespace app\components;

use Yii;

class OpenAIClientGeminiSql extends GeminiClient
{
    /**
     * Sends a question to Gemini through the shared secure transport.
     *
     * This function constructs a prompt containing information about the Yii2 application
     * and the user's question, sends a request to Gemini, and processes the response.
     * Transport errors and diagnostics are handled without recording response bodies.
     *
     * @param string $question The user's question to be sent to the OpenAI API.
     * @param \app\models\Usuario $user The user object representing the current user.
     * @return array An associative array containing the status of the execution, a message,
     *               and the response content from the API if successful.
     */

    protected function askConfiguredQuestion($question, $user)
    {

        $systemInstruction_text = $this->getSystemInstruction_text();
        $systemInstruction_text .= "Este es el esquema de la base de datos: " . json_encode($this->getSchema()) . "\n";
        $systemInstruction_text.="Usuario que realiza esta consulta y sus permisos:" .  $this->getUserAndHisAccess($user->id). "\n:";


        $data = [
            'contents' => [
                ['parts' => [['text' => $question]]]
            ],
            "systemInstruction" => [
                "role" => "user",
                "parts" => [
                    ["text" => $systemInstruction_text]
                ]
            ],


            "generationConfig" => [
                "maxOutputTokens" => 8192,
                "responseMimeType" => "text/plain"
            ]

        ];



        $result = $this->sendRequest($data);
        if ($result['st'] === 'error') {
            return $result;
        }
        $responseData = $result['data'];

        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])
            && is_string($responseData['candidates'][0]['content']['parts'][0]['text'])) {


            $respuesta=$responseData['candidates'][0]['content']['parts'][0]['text'];

            $consultaSQL = preg_replace('/```sql\s+|```/', '', $respuesta);


            return [
                'st' => 'ok',
                'msg' => 'ejecucion OK ',
                'response' => $consultaSQL
            ];


        } else {

            return [
                'st' => 'error',
                'msg' => "Error al obtener respuesta de Gemini"
            ];


        }


    }


    private function getSystemInstruction_text()
    {
        // Aquí puedes cargar los datos de los modelos desde tu archivo JSON
        $filePath = Yii::getAlias('@app/data/gemini/systemInstruction_sql.txt');
        return file_get_contents($filePath);

    }

    private function getSchema()
    {
        $filePath = Yii::getAlias('@app/data/schema.json');
        return json_decode(file_get_contents($filePath), true);
    }


    private function getUserAndHisAccess($user_id)
    {
        $db = Yii::$app->db;
        $sql = "
            SELECT q.usuario, q.login, q.nivel, q.activo, q.grupo_acceso, q.permiso, q.acceso
            FROM (
                SELECT
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario, 
                    u.login, u.nivel, u.activo,
                    ga.descr AS grupo_acceso,
                    a.descr AS permiso, a.acceso
                FROM usuario u
                LEFT JOIN grupos_accesos_usuarios gau ON u.id = gau.id_usuario
                LEFT JOIN grupo_acceso ga ON gau.id_grupo_acceso = ga.id
                LEFT JOIN grupos_accesos_accesos gaa ON ga.id = gaa.id_grupo_acceso
                LEFT JOIN acceso a ON gaa.id_acceso = a.id
                where u.id=$user_id
                UNION
                SELECT
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                    u.login, u.nivel, u.activo,
                    NULL AS grupo_acceso,
                    a.descr AS permiso, a.acceso
                FROM usuario u
                LEFT JOIN usuarios_accesos ua ON u.id = ua.id_usuario
                LEFT JOIN acceso a ON ua.id_accesos = a.id
                where u.id=$user_id
            ) q
            WHERE q.acceso IS NOT NULL
        ";

        try {
            // Ejecutar la consulta
            $command = $db->createCommand($sql);
            $result = $command->queryAll();


            $json= json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
           

        } catch (\Exception $e) {
            // Manejo de errores
            $this->recordDiagnostic('context_error');

            $json= json_encode(null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }



        return $json;
    }

}
