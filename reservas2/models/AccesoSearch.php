<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Acceso;

/**
 * AccesoSearch represents the model behind the search form about `app\models\Acceso`.
 */
class AccesoSearch extends Acceso
{

    public $esAccesoUsuario;
    public $esAccesoGrupo;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'updated_by', 'esAccesoUsuario', 'esAccesoGrupo'], 'integer'],
            [['descr', 'created_at', 'updated_at', 'esAccesoUsuario', 'esAccesoGrupo'], 'safe'],
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
        $query = Acceso::find();



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
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr]);
        $query->andFilterWhere(['like', 'acceso', $this->acceso]);

        return $dataProvider;
    }




    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param Identificador $user
     *
     * @return ActiveDataProvider
     */
    public function searchUsuario($params, $id_usuario,$user)
    {
        //$query = Acceso::find();

        $q=$user->accesosDisponibles();
        $query =$q;


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-accesos',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->esAccesoUsuario != null && $id_usuario != null) {
            if ($this->esAccesoUsuario == 1) {
                $query =$q //Acceso::find()
                    ->joinWith('usuarios')
                    ->where(['usuario.id' => $id_usuario])
                    ->distinct(true);


            } else {
                $query = $q //Acceso::find()
                    ->joinWith('usuarios')
                    ->where([
                        'or',
                        ['usuario.id' => null], // Incluye grupos sin usuarios
                        ['not', ['usuario.id' => $id_usuario]] // Excluye los que tienen el usuario dado
                    ])
                    ->andWhere([
                        'not in',
                        'acceso.id',
                        (new \yii\db\Query())
                            ->select('id_accesos')
                            ->from('usuarios_accesos')
                            ->where(['id_usuario' => $id_usuario])
                    ]);


            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-accesos',
                ]

            ]);

        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr]);
        $query->andFilterWhere(['like', 'acceso', $this->acceso]);

        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchGrupo($params, $id_grupo,$user)
    {
        //$query = Acceso::find();

        $q =$user->accesosDisponibles();
        $query = $q;
        



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-accesos',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->esAccesoGrupo != null && $id_grupo != null) {
            if ($this->esAccesoGrupo == 1) {
                $query = $q //Acceso::find()
                    ->joinWith('grupoAccesos')
                    ->where(['grupo_acceso.id' => $id_grupo])
                    ->distinct(true);


            } else {
                $query = $q //Acceso::find()
                    ->joinWith('grupoAccesos')
                    ->where([
                        'or',
                        ['grupo_acceso.id' => null], // Incluye grupos sin usuarios
                        ['not', ['grupo_acceso.id' => $id_grupo]] // Excluye los que tienen el usuario dado
                    ])
                    ->andWhere([
                        'not in',
                        'acceso.id',
                        (new \yii\db\Query())
                            ->select('id_acceso')
                            ->from('grupos_accesos_accesos')
                            ->where(['id_grupo_acceso' => $id_grupo])
                    ]);


            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-accesos',
                ]

            ]);

        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr]);
        $query->andFilterWhere(['like', 'acceso', $this->acceso]);

        return $dataProvider;
    }


}
