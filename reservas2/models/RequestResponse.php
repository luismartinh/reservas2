<?php

namespace app\models;

use \app\models\base\RequestResponse as BaseRequestResponse;
use Yii;

/**
 * This is the model class for table "request_responses".
 */
class RequestResponse extends BaseRequestResponse
{

    
    /**
     * Creates a new message (request response) and saves it in the database.
     * @param RequestReserva $requestReserva the request associated with the message
     * @param string $message the content of the message
     * @param bool $isResponse whether the message is a response or not
     * @return array with keys 'success' and 'message'
     */
    public static function newMessage($requestReserva, $message, $isResponse)
    {
        $msg = new RequestResponse();
        $msg->id_request = $requestReserva->id;
        $msg->fecha = date('Y-m-d H:i:s');
        $msg->response = $message;
        $msg->is_response = $isResponse ? 1 : 0; // 0 = consulta del cliente

        if (!$msg->save()) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'No se pudo guardar el mensaje.'),
            ];
        }

        return [
            'success' => true,
            'message' => null,
        ];

    }

}
