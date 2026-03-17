<?php

namespace App\Services\Trading;

use App\Models\Trade;
use App\Models\Import;

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
            'dolar' => ['gross' => 0],
            'indice' => ['gross' => 0]
        ];

        // 🔹 BRUTO (CORRETO)
        $grouped = [];

        foreach ($trades as $t) {
            $grouped[$t->asset][] = $t;
        }

        foreach ($grouped as $asset => $ops) {

            $buy = 0;
            $sell = 0;

            foreach ($ops as $trade) {

                $value = $trade->quantity * $trade->price;

                if ($trade->side === 'buy') $buy += $value;
                if ($trade->side === 'sell') $sell += $value;
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

            $markets[$market]['gross'] += $gross;
        }

        // 🔥 LÍQUIDO REAL (SEM DISTORÇÃO)
        $importIds = $trades->pluck('import_id')->unique();

        $imports = Import::whereIn('id', $importIds)->get();

        $totalNet = $imports->sum('net_total');

        return [
            [
                'market' => 'Mercado futuro dólar',
                'gross' => round($markets['dolar']['gross'], 2),
                'net' => null // 🔥 não mentimos dado
            ],
            [
                'market' => 'Mercado futuro índice',
                'gross' => round($markets['indice']['gross'], 2),
                'net' => null // 🔥 não mentimos dado
            ],
            [
                'market' => 'TOTAL REAL',
                'gross' => round($markets['dolar']['gross'] + $markets['indice']['gross'], 2),
                'net' => round($totalNet, 2)
            ]
        ];
    }
}