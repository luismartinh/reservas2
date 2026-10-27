<?php
namespace app\models;

class AsistenteEliminarPagoForm extends \yii\base\Model
{
    public $indice;
    public function rules()
    {
        return [[['indice'], 'required'], [['indice'], 'integer', 'min' => 0]];
    }
}
