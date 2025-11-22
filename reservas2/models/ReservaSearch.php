<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Reserva;

/**
 * ReservaSearch represents the model behind the search form about `app\models\Reserva`.
 */
class ReservaSearch extends Reserva
{
    public $denominacion;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'id_locador', 'pax', 'id_estado', 'created_by', 'updated_by'], 'integer'],
            [['fecha', 'desde', 'hasta', 'obs', 'created_at', 'updated_at','denominacion'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Reserva::find();



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
// $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'fecha' => $this->fecha,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'id_locador' => $this->id_locador,
            'pax' => $this->pax,
            'id_estado' => $this->id_estado,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'obs', $this->obs]);

        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchIndex($params)
    {
        $query = Reserva::find();



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'id_locador' => $this->id_locador,
            'pax' => $this->pax,
            'id_estado' => $this->id_estado,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'obs', $this->obs]);

        if (!empty($this->fecha)) {
            //03-02-2025 - 04-02-2025
            $range = explode(' - ', $this->fecha);
            $query->andFilterWhere([
                'between',
                'fecha',
                Utils::DMY2SQLdate($range[0], '-'),
                Utils::DMY2SQLdate($range[1], '-', '23:59:59')
            ]);
        }

        if (!empty($this->desde)) {
            //2025-12-30
            $query->andFilterWhere([
                'between',
                'desde',
                $this->desde . " 00:00:00",
                $this->desde . " 23:59:59",
            ]);
        }


        if (!empty($this->hasta)) {
            //2025-12-30
            $query->andFilterWhere([
                'between',
                'hasta',
                $this->hasta . " 00:00:00",
                $this->hasta . " 23:59:59",
            ]);
        }



        return $dataProvider;
    }

}
