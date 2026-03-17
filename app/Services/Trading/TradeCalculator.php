<?php

namespace App\Services\Trading;

use App\Models\Trade;

class TradeCalculator
{

    public static function monthlyResult($userId, $year, $month)
    {

        $trades = Trade::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->whereMonth('trade_date', $month)
            ->orderBy('trade_date', 'desc')
            ->get();

        // 🔹 agrupar por ativo
        $grouped = [];

        foreach ($trades as $t) {
            $grouped[$t->asset][] = $t;
        }

        $totalResult = 0;
        $details = [];

        foreach ($grouped as $asset => $ops) {

            $buy = 0;
            $sell = 0;
            $qty = 0;

            foreach ($ops as $trade) {

                $value = $trade->quantity * $trade->price;

                if ($trade->side === 'buy') {
                    $buy += $value;
                    $qty += $trade->quantity;
                }

                if ($trade->side === 'sell') {
                    $sell += $value;
                }
            }

            $multiplier = self::getMultiplier($asset);

            $result = ($sell - $buy) * $multiplier;

            $totalResult += $result;

            $details[] = [
                'asset' => $asset,
                'buy_total' => $buy,
                'sell_total' => $sell,
                'quantity' => $qty,
                'multiplier' => $multiplier,
                'result' => $result
            ];
        }

        return [
            'total_result' => $totalResult,
            'details' => $details
        ];
    }

    private static function getMultiplier($asset)
    {
        if (str_starts_with($asset, 'WIN')) {
            return 0.2;
        }

        if (str_starts_with($asset, 'WDO')) {
            return 10;
        }

        return 1;
    }
}