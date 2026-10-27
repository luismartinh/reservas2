<?php
namespace app\models;

class AsistenteEliminarReservaForm extends \yii\base\Model
{
    public $revisado;
    public function rules()
    {
        return [[['revisado'], 'required'], [['revisado'], 'compare', 'compareValue' => '1',
            'message' => 'Confirmá que revisaste la reserva y el impacto de eliminarla.']];
    }
    public function attributeLabels()
    {
        return ['revisado' => 'Revisé que esta es la reserva cargada por error y entiendo que se quitará su ocupación de cabañas.'];
    }
}
