<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\GrupoAcceso;

/**
 * GrupoAccesoSearch represents the model behind the search form about `app\models\GrupoAcceso`.
 */
class GrupoAccesoSearch extends GrupoAcceso
{

    public $esUsuarioGrupo;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'updated_by', 'esUsuarioGrupo', 'nivel'], 'integer'],
            [['descr', 'created_at', 'updated_at', 'esUsuarioGrupo', 'nivel'], 'safe'],
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
        $query = GrupoAcceso::find();



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
            'nivel' => $this->nivel,
        ]);

        $query->andFilterWhere(['like', 'descr', $this->descr]);

        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchIndex($params,$nivel)
    {

        $query = GrupoAcceso::find()->alias('grupo_acceso')->where(['>=', 'grupo_acceso.nivel', $nivel]);

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
            'grupo_acceso.id' => $this->id,
            'grupo_acceso.created_by' => $this->created_by,
            'grupo_acceso.updated_by' => $this->updated_by,
            'grupo_acceso.created_at' => $this->created_at,
            'grupo_acceso.updated_at' => $this->updated_at,
            'grupo_acceso.nivel' => $this->nivel,
        ]);

        $query->andFilterWhere(['like', 'grupo_acceso.descr', $this->descr]);

        return $dataProvider;
    }




    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchUsuario($params, $id_usuario,$nivel)
    {
        //$query = GrupoAcceso::find();

        $query = GrupoAcceso::find()->alias('grupo_acceso')->where(['>=', 'grupo_acceso.nivel', $nivel]);



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-grupoaccesos',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->esUsuarioGrupo !== null && $id_usuario !== null) {
            if ($this->esUsuarioGrupo == 1) {
                $query->joinWith('usuarios')
                    ->andWhere(['usuario.id' => $id_usuario])
                    ->distinct(true);


            } else {
                $query->joinWith('usuarios')
                    ->andWhere([
                        'or',
                        ['usuario.id' => null], // Incluye grupos sin usuarios
                        ['not', ['usuario.id' => $id_usuario]] // Excluye los que tienen el usuario dado
                    ])
                    ->andWhere([
                        'not in',
                        'grupo_acceso.id',
                        (new \yii\db\Query())
                            ->select('id_grupo_acceso')
                            ->from('grupos_accesos_usuarios')
                            ->where(['id_usuario' => $id_usuario])
                    ]);


            }

        }

        $query->andFilterWhere([
            'grupo_acceso.id' => $this->id,
            'grupo_acceso.created_by' => $this->created_by,
            'grupo_acceso.updated_by' => $this->updated_by,
            'grupo_acceso.created_at' => $this->created_at,
            'grupo_acceso.updated_at' => $this->updated_at,
            'grupo_acceso.nivel' => $this->nivel,
        ]);

        $query->andFilterWhere(['like', 'grupo_acceso.descr', $this->descr]);

        return $dataProvider;
    }

}
