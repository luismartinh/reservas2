<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Locador;

/**
 * LocadorSearch represents the model behind the search form about `app\models\Locador`.
 */
class LocadorSearch extends Locador
{
    public $buscar;
    public $campo = 'todos';

    public static function camposBusqueda()
    {
        return ['nombre' => 'denominacion', 'documento' => 'documento', 'telefono' => 'telefono',
            'email' => 'email', 'direccion' => 'domicilio', 'observaciones' => 'obs',
            'localidad' => 'localidad', 'provincia' => 'provincia', 'calificacion' => 'calificacion'];
    }

    public static function opcionesBusqueda()
    {
        return ['todos' => 'Todos los campos', 'nombre' => 'Nombre y apellido', 'documento' => 'Documento',
            'telefono' => 'Teléfono', 'email' => 'Email', 'direccion' => 'Dirección',
            'observaciones' => 'Observaciones', 'localidad' => 'Localidad', 'provincia' => 'Provincia', 'calificacion' => 'Calificación'];
    }

    public static function aplicarBusqueda($query, $text, $field = 'todos', $porPalabras = false)
    {
        $columns = self::camposBusqueda();
        if ($field !== 'todos' && !isset($columns[$field])) { throw new \InvalidArgumentException('Campo de búsqueda inválido.'); }
        if ($field !== 'todos') {
            $columns = [$field => $columns[$field]];
        }
        if ($text === null || trim($text) === '') { return $query; }
        if ($field === 'calificacion' && !preg_match('/^[0-5]$/D', trim($text))) { return $query->andWhere('0=1'); }
        foreach ($porPalabras ? preg_split('/\s+/u', trim($text)) : [trim($text)] as $term) {
            $condition = ['or'];
            foreach ($columns as $key => $column) {
                if ($key === 'localidad') {
                    $condition[] = ['id_localidad' => Localidad::find()->select('id')->where(['like', 'descr', $term])];
                } elseif ($key === 'provincia') {
                    $condition[] = ['id_localidad' => Localidad::find()->alias('l')->select('l.id')
                        ->innerJoin(['p' => Provincia::tableName()], 'p.id = l.id_provincia')->where(['like', 'p.descr', $term])];
                } elseif ($key === 'calificacion' && $field === 'calificacion' && trim($term) === '0') {
                    $condition[] = ['or', ['calificacion' => 0], ['calificacion' => null]];
                } elseif ($key === 'calificacion' && $field === 'calificacion') {
                    $condition[] = ['calificacion' => trim($term)];
                } else { $condition[] = ['like', $column, $term]; }
            }
            $query->andWhere($condition);
        }
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['buscar'], 'trim'],
            [['buscar'], 'string', 'max' => 150],
            [['campo'], 'in', 'range' => array_keys(self::opcionesBusqueda())],
            [['id', 'created_by', 'updated_by', 'calificacion', 'id_localidad', 'id_provincia'], 'integer'],
            [['denominacion', 'documento', 'email', 'telefono', 'domicilio', 'documentos', 'obs', 'created_at', 'updated_at'], 'safe'],
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
        $query = Locador::find()->with('localidad.provincia');



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->andWhere('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'calificacion' => $this->calificacion,
            'id_localidad' => $this->id_localidad,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'denominacion', $this->denominacion])
            ->andFilterWhere(['like', 'documento', $this->documento])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'telefono', $this->telefono])
            ->andFilterWhere(['like', 'domicilio', $this->domicilio])
            ->andFilterWhere(['like', 'documentos', $this->documentos])
            ->andFilterWhere(['like', 'obs', $this->obs]);


        self::aplicarBusqueda($query, $this->buscar, $this->campo);
        if ($this->id_provincia !== null && $this->id_provincia !== '') {
            $query->andWhere(['id_localidad' => Localidad::find()->select('id')
                ->where(['id_provincia' => $this->id_provincia])]);
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
    public function searchIndex($params)
    {
        return $this->search($params);
    }
}
