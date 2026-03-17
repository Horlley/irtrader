<?php

namespace App\Services\Trading;

use App\Models\Trade;

class MonthlyMarketResultService
{

    public static function calculate($userId, $year, $month)
    {

        $trades = Trade::with('import')
            ->where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->whereMonth('trade_date', $month)
            ->get();

        // 🔹 agrupar por ativo
        $grouped = [];

        foreach ($trades as $t) {
            $grouped[$t->asset][] = $t;
        }

        $markets = [
            'dolar' => 0,
            'indice' => 0
        ];

        foreach ($grouped as $asset => $ops) {

            $buy = 0;
            $sell = 0;

            foreach ($ops as $trade) {

                $value = $trade->quantity * $trade->price;

                if ($trade->side === 'buy') {
                    $buy += $value;
                }

                if ($trade->side === 'sell') {
                    $sell += $value;
                }
            }

            // 🔹 identifica mercado + multiplicador
            if (str_starts_with($asset, 'WDO')) {
                $multiplier = 10;
                $market = 'dolar';
            } elseif (str_starts_with($asset, 'WIN')) {
                $multiplier = 0.2;
                $market = 'indice';
            } else {
                continue;
            }

            // 🔹 resultado bruto
            $result = ($sell - $buy) * $multiplier;

            // 🔥 custo da nota (uma vez só)
            $import = $ops[0]->import ?? null;
            $cost = $import->total_costs ?? 0;

            // 🔹 distribui custo proporcional simples
            $result -= $cost;

            $markets[$market] += $result;
        }

        return [
            [
                'market' => 'Mercado futuro dólar',
                'result' => round($markets['dolar'], 2)
            ],
            [
                'market' => 'Mercado futuro índice',
                'result' => round($markets['indice'], 2)
            ]
        ];
    }
}