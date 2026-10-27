<?php
namespace app\models;

class AsistentePagoForm extends \yii\base\Model
{
    public $monto;
    public $medio_pago;
    public $nota;
    public $acreditado;
    public $comprobante;

    public function rules()
    {
        return [
            [['monto', 'nota'], 'trim'],
            [['monto'], 'required'],
            [['monto'], 'match', 'pattern' => '/^\d{1,12}(?:[.,]\d{1,2})?$/D',
                'message' => 'Ingresá un importe positivo, sin separadores de miles y con hasta dos decimales.'],
            [['nota'], 'string', 'max' => 500],
            [['medio_pago'], 'in', 'range' => array_keys(self::medios())],
            [['acreditado'], 'compare', 'compareValue' => '1', 'message' => 'Confirmá que verificaste la acreditación del pago.'],
            [['acreditado'], 'required'],
            [['comprobante'], 'file', 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg', 'jpeg', 'pdf'],
                'checkExtensionByMimeType' => true, 'maxSize' => 5 * 1024 * 1024],
        ];
    }

    public function attributeLabels()
    {
        return ['medio_pago' => 'Medio de pago (opcional)', 'monto' => 'Monto del pago', 'nota' => 'Nota del pago', 'comprobante' => 'Comprobante (opcional)',
            'acreditado' => 'Verifiqué la recepción/acreditación del pago (incluido el efectivo). Entiendo que Confirmado verifica el acumulado de pagos de la solicitud.'];
    }

    public static function medios()
    {
        return ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito',
            'tarjeta' => 'Tarjeta', 'otro' => 'Otro'];
    }

    public function centavos()
    {
        $parts = explode('.', str_replace(',', '.', $this->monto));
        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
