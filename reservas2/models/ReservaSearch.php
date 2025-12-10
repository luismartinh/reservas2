<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Reserva;
use app\models\Cabana;
use app\models\RequestReserva;

/**
 * ReservaSearch represents the model behind the search form about `app\models\Reserva`.
 */
class ReservaSearch extends Reserva
{
    public $denominacion;
    public $codigo_reserva;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'id_locador', 'pax', 'id_estado', 'created_by', 'updated_by'], 'integer'],
            [['fecha', 'desde', 'hasta', 'obs', 'created_at', 'updated_at', 'denominacion', 'codigo_reserva'], 'safe'],
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

        // 🔍 Nuevo: filtro por código de reserva en RequestReserva
        if (!empty($this->codigo_reserva)) {
            $query
                ->joinWith(['requestReservas rr'])
                ->andFilterWhere(['like', 'rr.codigo_reserva', $this->codigo_reserva])
                ->groupBy('reservas.id');  // evita duplicados si hay varios requests por reserva
        }

        return $dataProvider;
    }


    /**
     * Normaliza el filtro de cabañas que viene por GET (cabanas[]).
     *
     * @param \yii\web\Request $request
     * @param string $paramName
     * @return array
     */
    public static function getSelectedCabanasFromRequest(\yii\web\Request $request, $paramName = 'cabanas')
    {
        $selectedCabanas = $request->get($paramName, []);

        if (!is_array($selectedCabanas)) {
            $selectedCabanas = [$selectedCabanas];
        }

        // Opcional: limpiar vacíos
        $result = [];
        foreach ($selectedCabanas as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $result[] = $value; // dejamos tipo tal cual (string/int), como tenías antes
        }

        return $result;
    }


    /**
     * Obtiene reservas para el calendario de ocupación:
     * - normaliza el filtro cabanas[]
     * - aplica filtro por locador
     * - arma el query con solapamiento de fechas
     * - hace eager loading de relaciones usadas en el calendario
     *
     * @param \yii\web\Request $request
     * @param string $fromDate Y-m-d H:i:s
     * @param string $toDate   Y-m-d H:i:s
     * @return array [Reserva[] $reservas, array $selectedCabanas, int $idLocador, string $locadorLabel, string $codigoReserva]
     */
    public static function searchCalendario(\yii\web\Request $request, $fromDate, $toDate)
    {
        // Filtro de cabañas
        $selectedCabanas = self::getSelectedCabanasFromRequest($request);

        // Filtro de locador
        $idLocador = (int) $request->get('id_locador', 0);

        if ($idLocador === 0) {
            $idLocador = null;     // ← esto evita el "0" en Select2
            $locadorLabel = '';    // ← sin texto inicial
        }

        $reservaTable = self::tableName();
        $cabanaTable = Cabana::tableName();

        $query = self::find()
            ->joinWith(['reservaCabanas.cabana'])
            ->with([
                // usa el nombre correcto de tus relaciones
                'requestReservas',
                'estado',
                'locador',
                'reservaCabanas.cabana',
            ])
            ->andWhere([
                'NOT',
                [
                    'OR',
                    ['<', "$reservaTable.hasta", $fromDate],
                    ['>', "$reservaTable.desde", $toDate],
                ],
            ]);

        if (!empty($selectedCabanas)) {
            $query->andWhere(["$cabanaTable.id" => $selectedCabanas]);
        }

        if ($idLocador > 0) {
            $query->andWhere(["$reservaTable.id_locador" => $idLocador]);
        }

        // Filtro de codigo de reserva
        $codigoReserva = $request->get('codigo_reserva',null );

        // 🔍 Nuevo: filtro por código de reserva en RequestReserva
        if (!empty($codigoReserva)) {
            $query
                ->joinWith(['requestReservas rr'])
                ->andFilterWhere(['like', 'rr.codigo_reserva', $codigoReserva])
                ->groupBy('reservas.id');  // evita duplicados si hay varios requests por reserva
        }

        /** @var Reserva[] $reservas */
        $reservas = $query->all();

        // Armar label del locador (para mostrarse en el Select2)
        $locadorLabel = '';
        if ($idLocador > 0) {
            $locador = Locador::findOne($idLocador);
            if ($locador) {
                $parts = [];
                if ($locador->denominacion) {
                    $parts[] = $locador->denominacion;
                }
                if ($locador->documento) {
                    $parts[] = $locador->documento;
                }
                if ($locador->email) {
                    $parts[] = $locador->email;
                }
                $locadorLabel = implode(' - ', $parts);
            }
        }

        return [$reservas, $selectedCabanas, $idLocador, $locadorLabel,$codigoReserva];
    }




}
