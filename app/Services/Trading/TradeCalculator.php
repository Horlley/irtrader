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
            ->orderBy('trade_date')
            ->get();

        $buyTotal = 0;
        $sellTotal = 0;

        foreach ($trades as $trade) {

            $value = $trade->quantity * $trade->price;

            if ($trade->side === 'buy') {
                $buyTotal += $value;
            }

            if ($trade->side === 'sell') {
                $sellTotal += $value;
            }

        }

        $result = $sellTotal - $buyTotal;

        return [
            'buy_total' => $buyTotal,
            'sell_total' => $sellTotal,
            'result' => $result
        ];
    }

}