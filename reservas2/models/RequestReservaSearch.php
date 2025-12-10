<?php

namespace app\models;

use app\helpers\Utils;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\RequestReserva;

/**
 * RequestReservaSearch represents the model behind the search form about `app\models\RequestReserva`.
 */
class RequestReservaSearch extends RequestReserva
{

    public $impagas;
    public $vencidas;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'pax', 'id_estado', 'id_reserva', 'created_by', 'updated_by'], 'integer'],
            [
                [
                    'fecha',
                    'desde',
                    'hasta',
                    'denominacion',
                    'email',
                    'hash',
                    'obs',
                    'fecha_request_pago',
                    'registro_pagos',
                    'email_token',
                    'email_token_expira',
                    'created_at',
                    'updated_at',
                    'impagas',
                    'vencidas',
                    'codigo_reserva'
                ],
                'safe'
            ],
            [['total', 'pagado'], 'number'],
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
        $query = RequestReserva::find();



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
            'pax' => $this->pax,
            'total' => $this->total,
            'id_estado' => $this->id_estado,
            'id_reserva' => $this->id_reserva,
            'fecha_request_pago' => $this->fecha_request_pago,
            'pagado' => $this->pagado,
            'email_token_expira' => $this->email_token_expira,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'denominacion', $this->denominacion])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'hash', $this->hash])
            ->andFilterWhere(['like', 'obs', $this->obs])
            ->andFilterWhere(['like', 'registro_pagos', $this->registro_pagos])
            ->andFilterWhere(['like', 'codigo_reserva', $this->codigo_reserva])
            ->andFilterWhere(['like', 'email_token', $this->email_token]);

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
        $query = RequestReserva::find()->joinWith(['estado'])
        ;



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
            'pax' => $this->pax,
            'total' => $this->total,
            'id_estado' => $this->id_estado,
            'id_reserva' => $this->id_reserva,
            'fecha_request_pago' => $this->fecha_request_pago,
            'pagado' => $this->pagado,
            'email_token_expira' => $this->email_token_expira,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'hash', $this->hash])
            ->andFilterWhere(['like', 'obs', $this->obs])
            ->andFilterWhere(['like', 'registro_pagos', $this->registro_pagos])
            ->andFilterWhere(['like', 'codigo_reserva', $this->codigo_reserva])
            ->andFilterWhere(['like', 'email_token', $this->email_token]);

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

        if (!empty($this->denominacion)) {
            $query->andFilterWhere(['like', 'denominacion', $this->denominacion])
                ->orFilterWhere(['like', 'email', $this->denominacion])
            ;

        }

        if (!empty($this->impagas)) {
            $query->andWhere('pagado < total');
        }

        if (!empty($this->vencidas)) {
            // 👉 Configuración general RESERVA_CFG
            $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
            $confirmar_pago_expira = (int) ($cfg['max_horas_venc']['confirmar_pago'] ?? 48);
            $fecha_confirmar_pago_expira = (new \DateTime())->modify("+{$confirmar_pago_expira} hours");
            $hr_eliminar = (int) ($cfg['max_horas_venc']['request_reserva'] ?? 24);
            $limite = (new \DateTime("-{$hr_eliminar} hours"))->format('Y-m-d H:i:s');


            $query->andWhere("(
                estados.slug in ('pendiente-email-verificado','pendiente-email-contestado')
                and fecha < '{$fecha_confirmar_pago_expira->format('Y-m-d H:i:s')}'
                )
                or (
                estados.slug in ('pendiente-email-verificar') 
                and fecha < '{$limite}'
                )
             ");
        }


        return $dataProvider;
    }

}
