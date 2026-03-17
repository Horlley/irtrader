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

        $markets = [
            'dolar' => ['gross' => 0, 'net' => 0],
            'indice' => ['gross' => 0, 'net' => 0]
        ];

        $grouped = [];

        foreach ($trades as $t) {
            $grouped[$t->asset][] = $t;
        }

        // 🔥 total bruto geral (para rateio)
        $totalGrossAll = 0;
        $assetResults = [];

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

            if (str_starts_with($asset, 'WDO')) {
                $multiplier = 10;
                $market = 'dolar';
            } elseif (str_starts_with($asset, 'WIN')) {
                $multiplier = 0.2;
                $market = 'indice';
            } else {
                continue;
            }

            $gross = ($sell - $buy) * $multiplier;

            $assetResults[] = [
                'asset' => $asset,
                'market' => $market,
                'gross' => $gross,
                'import' => $ops[0]->import ?? null
            ];

            $totalGrossAll += abs($gross);
        }

        // 🔥 agora aplica rateio proporcional
        foreach ($assetResults as $item) {

            $market = $item['market'];
            $gross = $item['gross'];
            $import = $item['import'];

            $totalCost = $import->total_costs ?? 0;

            $proportion = $totalGrossAll > 0
                ? abs($gross) / $totalGrossAll
                : 0;

            $costShare = $totalCost * $proportion;

            $net = $gross - $costShare;

            $markets[$market]['gross'] += $gross;
            $markets[$market]['net'] += $net;
        }

        return [
            [
                'market' => 'Mercado futuro dólar',
                'gross' => round($markets['dolar']['gross'], 2),
                'net' => round($markets['dolar']['net'], 2)
            ],
            [
                'market' => 'Mercado futuro índice',
                'gross' => round($markets['indice']['gross'], 2),
                'net' => round($markets['indice']['net'], 2)
            ]
        ];
    }
}