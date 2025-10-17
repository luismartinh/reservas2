<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Usuario;

/**
 * UsuarioSearch represents the model behind the search form about `app\models\Usuario`.
 */
class UsuarioSearch extends Usuario
{
    public $esUsuarioGrupo;

    public $esUsuarioSucursal;
    public $esUsuarioPuntoVenta;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'id',
                    'activo',
                    'created_at',
                    'updated_at',
                    'created_by',
                    'updated_by',
                    'esUsuarioGrupo',
                    'nivel',
                    'esUsuarioSucursal',
                    'esUsuarioPuntoVenta'
                ],
                'integer'
            ],
            [
                [
                    'login',
                    'nombre',
                    'apellido',
                    'pwd',
                    'id_session',
                    'last_login_time',
                    'last_login_ip',
                    'codigo',
                    'auth_key',
                    'password_hash',
                    'password_reset_token',
                    'email',
                    'user_sign_token',
                    'access_token',
                    'locate',
                    'esUsuarioGrupo',
                    'nivel',
                    'esUsuarioSucursal',
                    'esUsuarioPuntoVenta'
                ],
                'safe'
            ],
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
        $query = Usuario::find();



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
            'last_login_time' => $this->last_login_time,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'nivel' => $this->nivel
        ]);

        $query->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'apellido', $this->apellido])
            ->andFilterWhere(['like', 'pwd', $this->pwd])
            ->andFilterWhere(['like', 'id_session', $this->id_session])
            ->andFilterWhere(['like', 'last_login_ip', $this->last_login_ip])
            ->andFilterWhere(['like', 'codigo', $this->codigo])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'user_sign_token', $this->user_sign_token])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'locate', $this->locate]);

        return $dataProvider;
    }


    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchIndex($params, $nivel)
    {
        //$query = Usuario::find();

        $query = Usuario::find()->where(['>=', 'nivel', $nivel]);



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'nivel' => $this->nivel
        ]);

        $query->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'apellido', $this->apellido])
            ->andFilterWhere(['like', 'pwd', $this->pwd])
            ->andFilterWhere(['like', 'id_session', $this->id_session])
            ->andFilterWhere(['like', 'last_login_ip', $this->last_login_ip])
            ->andFilterWhere(['like', 'codigo', $this->codigo])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'user_sign_token', $this->user_sign_token])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'locate', $this->locate]);


        if ($this->last_login_time) {
            //03-02-2025 - 04-02-2025
            $range = explode(' - ', $this->last_login_time);
            $query->andFilterWhere([
                'between',
                'last_login_time',
                Utils::DMY2SQLdate($range[0], '-'),
                Utils::DMY2SQLdate($range[1], '-', '23:59:59')
            ]);
        }

        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchGrupo($params, $id_grupo, $nivel)
    {
        //$query = Usuario::find();

        $query = Usuario::find()->where(['>=', 'nivel', $nivel]);




        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-usuarios',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->esUsuarioGrupo != null && $id_grupo != null) {
            if ($this->esUsuarioGrupo == 1) {
                $query = Usuario::find()
                    ->joinWith('grupoAccesos')
                    ->where(['grupo_acceso.id' => $id_grupo])
                    ->distinct(true);


            } else {
                $query = Usuario::find()
                    ->joinWith('grupoAccesos')
                    ->where([
                        'or',
                        ['grupo_acceso.id' => null],
                        ['not', ['grupo_acceso.id' => $id_grupo]]
                    ])
                    ->andWhere([
                        'not in',
                        'usuario.id',
                        (new \yii\db\Query())
                            ->select('id_usuario')
                            ->from('grupos_accesos_usuarios')
                            ->where(['id_grupo_acceso' => $id_grupo])
                    ]);


            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-usuarios',
                ]

            ]);

        }

        $query->andFilterWhere([
            'id' => $this->id,
            'last_login_time' => $this->last_login_time,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'nivel' => $this->nivel
        ]);

        $query->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'apellido', $this->apellido])
            ->andFilterWhere(['like', 'pwd', $this->pwd])
            ->andFilterWhere(['like', 'id_session', $this->id_session])
            ->andFilterWhere(['like', 'last_login_ip', $this->last_login_ip])
            ->andFilterWhere(['like', 'codigo', $this->codigo])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'user_sign_token', $this->user_sign_token])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'locate', $this->locate]);


        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param int $id_sucursal
     * @param Usuario $user
     * 
     * @return ActiveDataProvider
     */
    public function searchSucursal($params, $id_sucursal, $user)
    {
        $query = Usuario::find()->where(['>=', 'nivel', $user->nivel]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-usuarios',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            //$query->where('0=1');

            return $dataProvider;
        }



        if ($this->esUsuarioSucursal != null && $id_sucursal != null) {


            if ($this->esUsuarioSucursal == 1) {
                $query = Usuario::find()->where(['>=', 'nivel', $user->nivel])
                    ->joinWith('sucursales')
                    ->where(['sucursal.id' => $id_sucursal])
                    ->distinct(true);


            } else {
                $query = Usuario::find()->where(['>=', 'nivel', $user->nivel])
                    ->joinWith('sucursales')
                    ->where([
                        'or',
                        ['sucursal.id' => null],
                        ['not', ['sucursal.id' => $id_sucursal]]
                    ])
                    ->andWhere([
                        'not in',
                        'usuario.id',
                        (new \yii\db\Query())
                            ->select('id_usuario')
                            ->from('sucursal_usuario')
                            ->where(['id_sucursal' => $id_sucursal])
                    ]);


            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-usuarios',
                ]

            ]);

        }

        $query->andFilterWhere([
            'id' => $this->id,
            'last_login_time' => $this->last_login_time,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'nivel' => $this->nivel
        ]);

        $query->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'apellido', $this->apellido])
            ->andFilterWhere(['like', 'pwd', $this->pwd])
            ->andFilterWhere(['like', 'id_session', $this->id_session])
            ->andFilterWhere(['like', 'last_login_ip', $this->last_login_ip])
            ->andFilterWhere(['like', 'codigo', $this->codigo])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'user_sign_token', $this->user_sign_token])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'locate', $this->locate]);

        return $dataProvider;
    }



    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param int $id_punto_venta
     * @param Usuario $user
     * 
     * @return ActiveDataProvider
     */
    public function searchPuntoVenta($params, $id_punto_venta, $user)
    {
        $query = Usuario::find()->where(['>=', 'nivel', $user->nivel]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageParam' => 'page-usuarios',
            ]

        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }



        if ($this->esUsuarioPuntoVenta != null && $id_punto_venta != null) {


            if ($this->esUsuarioPuntoVenta == 1) {
                $query = Usuario::find()->where(['>=', 'nivel', $user->nivel])
                    ->joinWith('puntoVentas')
                    ->where(['punto_venta.id' => $id_punto_venta])
                    ->distinct(true);


            } else {
                $query = Usuario::find()->where(['>=', 'nivel', $user->nivel])
                    ->joinWith('puntoVentas')
                    ->where([
                        'or',
                        ['punto_venta.id' => null],
                        ['not', ['punto_venta.id' => $id_punto_venta]]
                    ])
                    ->andWhere([
                        'not in',
                        'usuario.id',
                        (new \yii\db\Query())
                            ->select('id_usuario')
                            ->from('punto_venta_usuario')
                            ->where(['id_punto_venta' => $id_punto_venta])
                    ]);


            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-usuarios',
                ]

            ]);

        }

        $query->andFilterWhere([
            'id' => $this->id,
            'last_login_time' => $this->last_login_time,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'nivel' => $this->nivel
        ]);

        $query->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'apellido', $this->apellido])
            ->andFilterWhere(['like', 'pwd', $this->pwd])
            ->andFilterWhere(['like', 'id_session', $this->id_session])
            ->andFilterWhere(['like', 'last_login_ip', $this->last_login_ip])
            ->andFilterWhere(['like', 'codigo', $this->codigo])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'user_sign_token', $this->user_sign_token])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'locate', $this->locate]);

        return $dataProvider;
    }

}
