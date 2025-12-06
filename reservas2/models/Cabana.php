<?php

namespace app\models;

use \app\models\base\Cabana as BaseCabana;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cabanas".
 */
class Cabana extends BaseCabana
{
    public $color_cabana;

    // PALETA DE 16 COLORES
    public static $PALETA = [
        '#e6194B' => 'Rojo',
        '#3cb44b' => 'Verde',
        '#ffe119' => 'Amarillo',
        '#4363d8' => 'Azul',
        '#f58231' => 'Naranja',
        '#911eb4' => 'Violeta',
        '#46f0f0' => 'Cian',
        '#f032e6' => 'Magenta',
        '#bcf60c' => 'Lima',
        '#fabebe' => 'Rosa claro',
        '#008080' => 'Verde petróleo',
        '#e6beff' => 'Lavanda',
        '#9A6324' => 'Marrón',
        '#fffac8' => 'Crema',
        '#800000' => 'Bordeaux',
        '#aaffc3' => 'Verde pastel',
        '#d3d3d3' => 'Gris claro',
    ];

    public static function getPaletaTraducida(): array
    {
        $resultado = [];
        foreach (self::$PALETA as $hex => $label) {
            $resultado[$hex] = Yii::t('app', $label);
        }
        return $resultado;
    }


    /**
     * @inheritdoc
     */

    public function rules()
    {

        $parentRules = parent::rules();

        return ArrayHelper::merge($parentRules, [
            ['color_cabana', 'required'],
            ['color_cabana', 'string', 'max' => 20],
        ]);
    }


    public function afterFind()
    {
        parent::afterFind();

        if (is_array($this->config)) {
            $this->color_cabana = $this->config['color_cabana'] ?? null;
        }
    }


    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Asegurar array JSON
        $car = is_array($this->config) ? $this->config : [];

        // Guardar color dentro del JSON
        $car['color_cabana'] = $this->color_cabana;

        $this->config = $car;

        return true;
    }

    public static function coloresUsados()
    {
        $models = self::find()->all();
        $usados = [];

        foreach ($models as $m) {
            if (is_array($m->config) && !empty($m->config['color_cabana'])) {
                $usados[] = $m->config['color_cabana'];
            }
        }

        return $usados;
    }

    public function getColor_cabana()
    {
        $config = $this->config ?: [];
        return $config['color_cabana'] ?? null;
    }

    public function setColor_cabana($value)
    {
        $config = $this->config ?: [];
        $config['color_cabana'] = $value;
        $this->config = $config;
    }


    public static function getNumerosDisponibles(?Cabana $model = null): array
    {
        // Ajustá este máximo según tus necesidades
        $maxNumero = 50;

        // Todos los posibles
        $todos = range(1, $maxNumero);

        // Números ya usados en otras cabañas
        $query = Cabana::find()
            ->select('numero')
            ->where(['not', ['numero' => null]]);

        // Si estamos editando, excluir la propia cabaña
        if ($model && !$model->isNewRecord) {
            $query->andWhere(['<>', 'id', $model->id]);
        }

        $usados = $query->column();   // array de números usados
        $usados = array_map('intval', $usados);

        // Números libres
        $libres = array_diff($todos, $usados);
        sort($libres);

        // Si estamos editando y el modelo ya tiene un número,
        // lo agregamos por si quedó fuera de "libres" (porque ya está usado).
        if ($model && $model->numero) {
            $numActual = (int) $model->numero;
            if (!in_array($numActual, $libres, true)) {
                array_unshift($libres, $numActual);
            }
        }

        // Transformar a formato [valor => texto]
        $result = [];
        foreach ($libres as $n) {
            $result[$n] = $n;
        }

        return $result;
    }
}
