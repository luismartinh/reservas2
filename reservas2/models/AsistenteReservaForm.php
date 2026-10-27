<?php
namespace app\models;

/** Used only by the private form, never loaded from model/tool arguments. */
class AsistenteReservaForm extends \yii\base\Model
{
    use UbicacionTrait;

    public $documento;
    public $denominacion;
    public $email;
    public $telefono;
    public $domicilio;
    public $obs;
    public $referencia;
    public $id_localidad;

    public function rules()
    {
        return array_merge($this->reglasUbicacion(), [
            [['documento', 'denominacion', 'email', 'telefono', 'domicilio', 'obs', 'referencia'], 'trim'],
            [['documento'], 'required'],
            [['documento', 'email', 'telefono'], 'string', 'max' => 45],
            [['denominacion', 'domicilio'], 'string', 'max' => 100],
            [['obs'], 'string', 'max' => 500],
            [['email'], 'email'],
            [['referencia'], 'string', 'max' => 45],
        ]);
    }

    public function attributeLabels()
    {
        return ['documento' => 'Documento', 'denominacion' => 'Nombre y apellido', 'email' => 'Email',
            'telefono' => 'Teléfono', 'domicilio' => 'Domicilio', 'obs' => 'Observaciones de la reserva',
            'referencia' => 'Referencia de la solicitud', 'id_provincia' => 'Provincia', 'id_localidad' => 'Localidad'];
    }

    public function locador()
    {
        if (!$this->validate()) { return null; }
        $existing = Locador::findOne(['documento' => $this->documento]);
        if ($existing) { return $existing; } // Never overwrite a customer through this flow.
        $locador = new Locador();
        foreach (['documento', 'denominacion', 'email', 'telefono', 'domicilio', 'id_localidad'] as $field) {
            $locador->$field = $this->$field;
        }
        if (!$locador->validate()) {
            foreach ($locador->getErrors() as $field => $errors) {
                foreach ($errors as $error) { $this->addError($field, $error); }
            }
            return null;
        }
        return $locador;
    }
}
