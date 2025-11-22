<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * Description of DisponibilidadSearch
 *
 * @author martinh
 * @property string $desde
 * @property string $hasta
 */
class DisponibilidadSearch extends Model
{
    public $desde;
    public $hasta;

    public $periodo;


    public function rules()
    {
        return [
            [['desde', 'hasta', 'periodo'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'desde' => Yii::t('app', 'Desde:'),
            'hasta' => Yii::t('app', 'Hasta:'),
            'periodo' => Yii::t('app', 'Periodo:'),
        ];
    }


    public function attributeHints()
    {
        return [
            'desde' => Yii::t('app', 'Indique la fecha de ingreso:'),
            'hasta' => Yii::t('app', 'Indique la fecha de salida:'),
            'periodo' => Yii::t('app', 'Indique el Periodo:'),
        ];
    }


    public function search($params)
    {
        // 1) Normalizar parámetros de entrada
        $this->load($params);

        if ($this->desde) {
            $dt = \DateTime::createFromFormat('d-m-Y', $this->desde)
                ?: \DateTime::createFromFormat('Y-m-d', $this->desde);
            $this->desde = $dt ? $dt->format('Y-m-d') : $this->desde;
        }
        if ($this->hasta) {
            $dt = \DateTime::createFromFormat('d-m-Y', $this->hasta)
                ?: \DateTime::createFromFormat('Y-m-d', $this->hasta);
            $this->hasta = $dt ? $dt->format('Y-m-d') : $this->hasta;
        }

        $query = Reserva::cabanasLibres($this->desde, $this->hasta);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['descr' => SORT_ASC],
            ],
        ]);
    }



}