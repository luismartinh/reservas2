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

    // Campos virtuales para traducciones de "caracteristicas"
    public $caracteristicas_es;
    public $caracteristicas_en;
    public $caracteristicas_pt_br;

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
     * Devuelve un array de líneas de características según el idioma actual.
     * - Soporta formato viejo: JSON que contiene un string (solo ES).
     * - Soporta formato nuevo: JSON con claves por idioma: ['es' => '...', 'en' => '...', 'pt-BR' => '...'].
     */
    public static function buildFeaturesLines($caracteristicas, ?string $descr = null): array
    {
        $featuresRaw = '';

        // 1) Si ya viene como array (JsonBehavior u otro)
        if (is_array($caracteristicas)) {
            $lang = Yii::$app->language;
            $defaultLang = 'es';
            $baseLang = substr($lang, 0, 2);

            if (isset($caracteristicas[$lang]) && is_string($caracteristicas[$lang])) {
                $featuresRaw = $caracteristicas[$lang];
            } elseif (isset($caracteristicas[$baseLang]) && is_string($caracteristicas[$baseLang])) {
                $featuresRaw = $caracteristicas[$baseLang];
            } elseif (isset($caracteristicas[$defaultLang]) && is_string($caracteristicas[$defaultLang])) {
                $featuresRaw = $caracteristicas[$defaultLang];
            } else {
                $first = reset($caracteristicas);
                if (is_string($first)) {
                    $featuresRaw = $first;
                }
            }

            // 2) Si viene como string (texto plano o JSON)
        } elseif (is_string($caracteristicas) && $caracteristicas !== '') {

            $decoded = json_decode($caracteristicas, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_string($decoded)) {
                    // Formato viejo: JSON que contiene un string (solo ES)
                    $featuresRaw = $decoded;
                } elseif (is_array($decoded)) {
                    // Formato nuevo: ['es' => '...', 'en' => '...', 'pt-BR' => '...']
                    $lang = Yii::$app->language;
                    $defaultLang = 'es';
                    $baseLang = substr($lang, 0, 2);

                    if (isset($decoded[$lang]) && is_string($decoded[$lang])) {
                        $featuresRaw = $decoded[$lang];
                    } elseif (isset($decoded[$baseLang]) && is_string($decoded[$baseLang])) {
                        $featuresRaw = $decoded[$baseLang];
                    } elseif (isset($decoded[$defaultLang]) && is_string($decoded[$defaultLang])) {
                        $featuresRaw = $decoded[$defaultLang];
                    } else {
                        $first = reset($decoded);
                        if (is_string($first)) {
                            $featuresRaw = $first;
                        }
                    }
                }
            } else {
                // No es JSON válido, lo usamos tal cual
                $featuresRaw = $caracteristicas;
            }

            // 3) Fallback: usar descr si no hay características
        } elseif (is_string($descr) && $descr !== '') {
            $featuresRaw = $descr;
        }

        if ($featuresRaw === '' || $featuresRaw === null) {
            return [];
        }

        // Partir en líneas y limpiar
        $lines = array_filter(
            array_map('trim', preg_split('/\R/', (string) $featuresRaw))
        );

        return $lines;
    }

    /**
     * Conveniencia de instancia: $cabana->featuresLines.
     */
    public function getFeaturesLines(): array
    {
        return self::buildFeaturesLines($this->caracteristicas, $this->descr);
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
            // Campos virtuales de características traducidas
            [['caracteristicas_es', 'caracteristicas_en', 'caracteristicas_pt_br'], 'safe'],
        ]);
    }


    public function afterFind()
    {
        parent::afterFind();

        // ======================
        // Color de cabaña (config)
        // ======================
        if (is_array($this->config)) {
            $this->color_cabana = $this->config['color_cabana'] ?? null;
        }

        // ======================
        // Características traducidas
        // ======================
        $value = $this->caracteristicas;

        if ($value === null || $value === '') {
            return;
        }

        // Si ya viene como array (lo más probable con JsonBehavior)
        if (is_array($value)) {
            // Caso multi-idioma directo
            if (isset($value['es']) || isset($value['en']) || isset($value['pt-BR'])) {
                $this->caracteristicas_es = $value['es'] ?? '';
                $this->caracteristicas_en = $value['en'] ?? '';
                $this->caracteristicas_pt_br = $value['pt-BR'] ?? '';
            } else {
                // Algún otro formato: tomamos el primero como español
                $first = reset($value);
                if (is_string($first)) {
                    $this->caracteristicas_es = $first;
                }
            }

            return;
        }

        // Si es string, puede ser JSON o texto plano
        if (!is_string($value)) {
            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_string($decoded)) {
                // Formato viejo: JSON con string en ES
                $this->caracteristicas_es = $decoded;
            } elseif (is_array($decoded)) {
                $this->caracteristicas_es = $decoded['es'] ?? '';
                $this->caracteristicas_en = $decoded['en'] ?? '';
                $this->caracteristicas_pt_br = $decoded['pt-BR'] ?? '';
            }
        } else {
            // Texto plano
            $this->caracteristicas_es = $value;
        }
    }


    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // ==========================
        // CONFIG: color_cabana
        // ==========================
        $config = is_array($this->config) ? $this->config : [];
        $config['color_cabana'] = $this->color_cabana;
        $this->config = $config;

        // ==========================
        // CARACTERISTICAS traducidas
        // ==========================
        $data = [];

        if (trim((string) $this->caracteristicas_es) !== '') {
            $data['es'] = $this->caracteristicas_es;
        }
        if (trim((string) $this->caracteristicas_en) !== '') {
            $data['en'] = $this->caracteristicas_en;
        }
        if (trim((string) $this->caracteristicas_pt_br) !== '') {
            $data['pt-BR'] = $this->caracteristicas_pt_br;
        }

        // Guardamos como array; el behavior (JsonBehavior, etc.) se encarga de convertir a JSON
        $this->caracteristicas = !empty($data) ? $data : null;

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
