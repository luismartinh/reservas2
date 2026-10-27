<?php
namespace app\models;

class AsistenteMensajeForm extends \yii\base\Model
{
    public $mensaje;
    public $conversacion;

    public function rules()
    {
        return [
            [['mensaje', 'conversacion'], 'required'],
            ['mensaje', 'string', 'max' => 2000],
            ['mensaje', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['conversacion', 'match', 'pattern' => '/^[a-f0-9]{32}$/D'],
        ];
    }
}
