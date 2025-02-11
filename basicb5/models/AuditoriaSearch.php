<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Auditoria;

/**
 * AuditoriaSearch represents the model behind the search form about `app\models\Auditoria`.
 */
class AuditoriaSearch extends Auditoria
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'updated_at'], 'integer'],
            [['tabla', 'changes', 'user', 'action', 'pkId', 'created_at'], 'safe'],
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
        $query = Auditoria::find();



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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'tabla', $this->tabla])
            ->andFilterWhere(['like', 'changes', $this->changes])
            ->andFilterWhere(['like', 'user', $this->user])
            ->andFilterWhere(['like', 'action', $this->action])
            ->andFilterWhere(['like', 'pkId', $this->pkId]);

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
        $query = Auditoria::find();



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => ['id'],
                'defaultOrder' => ['id' => SORT_DESC],
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            //'created_at' => $this->created_at,
            //'updated_at' => $this->updated_at,
        ]);

        if ($this->created_at) {
            //03-02-2025 - 04-02-2025
            $range = explode(' - ', $this->created_at);
            $query->andFilterWhere([
                'between',
                'created_at',
                Utils::DMY2SQLdate($range[0], '-'),
                Utils::DMY2SQLdate($range[1], '-', '23:59:59')
            ]);
        }


        $query->andFilterWhere(['like', 'tabla', $this->tabla])
            ->andFilterWhere(['like', 'changes', $this->changes])
            ->andFilterWhere(['like', 'user', $this->user])
            ->andFilterWhere(['like', 'action', $this->action])
            ->andFilterWhere(['like', 'pkId', $this->pkId]);

        return $dataProvider;
    }

}
