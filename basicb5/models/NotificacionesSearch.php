<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Notificaciones;

/**
 * NotificacionesSearch represents the model behind the search form about `app\models\Notificaciones`.
 */
class NotificacionesSearch extends Notificaciones
{

    public $fecha;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'id_msg', 'id_user', 'leida'], 'integer'],
            [['id', 'id_msg', 'id_user', 'leida','fecha','tabla'], 'safe'],

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
        $query = Notificaciones::find();



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
            'id_msg' => $this->id_msg,
            'id_user' => $this->id_user,
            'leida' => $this->leida,
        ]);

        $query->andFilterWhere(['like', 'tabla', $this->tabla]);

        return $dataProvider;
    }


    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchIndex($params,$id_usuario)
    {
        $query = Notificaciones::find()->where(['id_user'=>$id_usuario])->joinWith(['msg']);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => ['id','leida'],
                'defaultOrder' => ['leida' => SORT_ASC,'id' => SORT_DESC,],
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'id_msg' => $this->id_msg,
            'id_user' => $this->id_user,
            'leida' => $this->leida,
        ]);

        if ($this->fecha) {
            //03-02-2025 - 04-02-2025
            $range = explode(' - ', $this->fecha);
            $query->andFilterWhere([
                'between',
                'notif_mensajes.created_at',
                Utils::DMY2SQLdate($range[0], '-'),
                Utils::DMY2SQLdate($range[1], '-', '23:59:59')
            ]);
        }

        $query->andFilterWhere(['like', 'tabla', $this->tabla]);

        return $dataProvider;
    }    
}
