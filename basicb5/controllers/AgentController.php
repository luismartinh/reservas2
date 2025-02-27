<?php

namespace app\controllers;

use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use app\components\OpenAIClientGemini;
use app\components\OpenAIClientGeminiSql;
use app\models\GeneralForm;
use Yii;
use yii\data\ArrayDataProvider;
use yii\web\Controller;

class AgentController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['ask-gemini','aOPENAI_API_KEY_ENV'],
                            'roles' => ['@'],
                        ],
                    ]
                ]
            ]
        );
    }

    /*
    public function actionAskOpenai()
    {
        //$question = Yii::$app->request->post('question');
        $model = new GeneralForm();

        if ($model->load($this->request->post())) {

            $openAIClient = new OpenAIClient();
            $response = $openAIClient->askQuestion($model->question);
            return $this->render('response', ['response' => $response]);
        }

        return $this->render('ask', ['model' => $model]);
    }



    public function actionAskPawan()
    {
        $model = new GeneralForm();

        if ($model->load($this->request->post())) {

            $openAIClient = new OpenAIClientPawan();
            $response = $openAIClient->askQuestion($model->question);

            if ($response['st'] == 'error') {
                $model->addError('_exception', $response['msg']);
            }
        }

        return $this->render('ask_response_pawan', ['model' => $model, 'response' => $response['response'] ?? null]);
    }
        */

    public function actionAskGemini()
    {
        $model = new GeneralForm();

        if ($model->load($this->request->post())) {

            $openAIClient = new OpenAIClientGemini();
            $response = $openAIClient->askQuestion($model->question,Yii::$app->user->identity);

            if ($response['st'] == 'error') {
                $model->addError('_exception', $response['msg']);
            }
        }

        return $this->render('ask_response_gemini', ['model' => $model, 'response' => $response['response'] ?? null]);
    }




    public function actionAskGeminiSql()
    {

        $model = new GeneralForm();

        if ($model->load($this->request->post())) {

            if (!Yii::$app->request->isPjax) {

                $openAIClient = new OpenAIClientGeminiSql();
                $response = $openAIClient->askQuestion($model->question,Yii::$app->user->identity);

                if ($response['st'] == 'error') {
                    $model->addError('_exception', $response['msg']);
                    Yii::error($response['msg']);
                    return $this->render('ask_response_sql', ['model' => $model]);
                }

                $sql = $response['response'] ?? null;


                if (!preg_match('/^\s*SELECT/i', $sql)) {
                    Yii::error("Error $sql ");
                    $model->addError('_exception', "Error: $sql  ");
                    return $this->render('ask_response_sql', ['model' => $model]);
                }

                Yii::$app->session->set('last_sql_query', $sql);
                Yii::$app->session->set('last_sql_question', $model->question);


            } else {
                $sql = Yii::$app->session->get('last_sql_query');
            }

            try {
                // Ejecutar la consulta
                $data = Yii::$app->db->createCommand($sql)->queryAll();

                // Pasar los datos a la vista con un proveedor de datos
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $data,
                    'pagination' => [
                        'pageSize' => 10, // Paginación de 10 registros por página
                    ],
                ]);

                return $this->render('ask_response_sql', ['dataProvider' => $dataProvider, 'model' => $model]);
            } catch (\Exception $e) {
                Yii::error("Error en la consulta SQL: " . $e->getMessage() . ' ' . $sql);
                $model->addError('_exception', ($e->errorInfo[2] ?? $e->getMessage()) . ' ' . $sql);
                return $this->render('ask_response_sql', ['model' => $model]);

            }

        } else {
            if (Yii::$app->session->has('last_sql_question')) {
                $model->question = Yii::$app->session->get('last_sql_question');
            }

            if (Yii::$app->session->has('last_sql_query')) {
                $sql = Yii::$app->session->get('last_sql_query');
                $data = Yii::$app->db->createCommand($sql)->queryAll();
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $data,
                    'pagination' => [
                        'pageSize' => 10, // Paginación de 10 registros por página
                    ],
                ]);
                return $this->render('ask_response_sql', ['dataProvider' => $dataProvider, 'model' => $model]);
            }


        }

        return $this->render('ask_response_sql', ['model' => $model]);

    }


    /*

    public function actionAskSqln8n()
    {
        // Configura la URL del webhook de n8n
        $url = 'https://luismartinh.app.n8n.cloud/webhook-test/1c3b368b-38ff-4afb-8d52-9413e4055a69';
        //$url = 'https://luismartinh.app.n8n.cloud/webhook/1c3b368b-38ff-4afb-8d52-9413e4055a69';


        $model = new GeneralForm();




        if ($model->load($this->request->post())) {


            if (!Yii::$app->request->isPjax) {

                $client = new \GuzzleHttp\Client();

                $prompt = $this->getPromptSql();
                $prompt .= "Este es el esquema de la base de datos: " . json_encode($this->getSchema()) . "\n";
                $prompt .= "Esta es la pregunta del usuario: " . $model->question;


                try {
                    $response = $client->post($url, [
                        'json' => [
                            'json' => ['question' => $model->question, 'prompt' => $prompt],
                        ],
                    ]);

                    // Obtener el contenido de la respuesta
                    $body = $response->getBody();

                    $data = json_decode($body, true);

                    $sql = $data['output'];


                    if (!preg_match('/^\s*SELECT/i', $sql)) {
                        Yii::error("Error 'Solo se permiten consultas SELECT' ");
                        $model->addError('_exception', "Error 'Solo se permiten consultas SELECT' ");
                        return $this->render('ask_response_sql', ['model' => $model]);
                    }

                    Yii::$app->session->set('last_sql_query', $sql);




                } catch (\Exception $e) {
                    Yii::error("Error llamando a la API de n8n: " . $e->getMessage());
                    $model->addError('_exception', $e->errorInfo[2] ?? $e->getMessage());
                    return $this->render('ask_response_sql', ['model' => $model]);

                }


                Yii::$app->session->set('last_sql_query', $sql);
                Yii::$app->session->set('last_sql_question', $model->question);


            } else {
                $sql = Yii::$app->session->get('last_sql_query');
            }

            try {
                // Ejecutar la consulta
                $data = Yii::$app->db->createCommand($sql)->queryAll();

                // Pasar los datos a la vista con un proveedor de datos
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $data,
                    'pagination' => [
                        'pageSize' => 10, // Paginación de 10 registros por página
                    ],
                ]);

                return $this->render('ask_response_sql', ['dataProvider' => $dataProvider, 'model' => $model]);
            } catch (\Exception $e) {
                Yii::error("Error en la consulta SQL: " . $e->getMessage() . ' ' . $sql);
                $model->addError('_exception', ($e->errorInfo[2] ?? $e->getMessage()) . ' ' . $sql);
                return $this->render('ask_response_sql', ['model' => $model]);

            }


        } else {
            if (Yii::$app->session->has('last_sql_question')) {
                $model->question = Yii::$app->session->get('last_sql_question');
            }

            if (Yii::$app->session->has('last_sql_query')) {
                $sql = Yii::$app->session->get('last_sql_query');
                $data = Yii::$app->db->createCommand($sql)->queryAll();
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $data,
                    'pagination' => [
                        'pageSize' => 10, // Paginación de 10 registros por página
                    ],
                ]);
                return $this->render('ask_response_sql', ['dataProvider' => $dataProvider, 'model' => $model]);
            }


        }

        return $this->render('ask_response_sql', ['model' => $model]);
    }
    */

    // Método para cargar datos de controladores desde un archivo JSON
    private function getControllersData()
    {
        $filePath = Yii::getAlias('@app/data/controllers_data.json');
        return json_decode(file_get_contents($filePath), true);
    }

    // Método para cargar datos de modelos desde un archivo JSON
    private function getModelsData()
    {
        $filePath = Yii::getAlias('@app/data/models_data.json');
        return json_decode(file_get_contents($filePath), true);
    }

    private function getPrompt()
    {
        $filePath = Yii::getAlias('@app/data/prompt.txt');
        return file_get_contents($filePath);
    }

    private function getPromptSql()
    {
        $filePath = Yii::getAlias('@app/data/promptsql.txt');
        return file_get_contents($filePath);
    }


    private function getSchema()
    {
        $filePath = Yii::getAlias('@app/data/schema.json');
        return json_decode(file_get_contents($filePath), true);
    }

}
