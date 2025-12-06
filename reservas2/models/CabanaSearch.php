<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Cabana;

/**
 * CabanaSearch represents the model behind the search form about `app\models\Cabana`.
 */
class CabanaSearch extends Cabana
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'max_pax', 'activa','numero', 'created_by', 'updated_by'], 'integer'],
            [['descr', 'checkin', 'checkout', 'caracteristicas', 'created_at', 'updated_at', 'numero'], 'safe'],
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
        $query = Cabana::find();



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
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'max_pax' => $this->max_pax,
            'numero' => $this->numero,
            'activa' => $this->activa,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr])
            ->andFilterWhere(['like', 'caracteristicas', $this->caracteristicas]);

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
        $query = Cabana::find();



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
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'max_pax' => $this->max_pax,
            'numero' => $this->numero,
            'activa' => $this->activa,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr])
            ->andFilterWhere(['like', 'caracteristicas', $this->caracteristicas]);

        return $dataProvider;
    }

}
