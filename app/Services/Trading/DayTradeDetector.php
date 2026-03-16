<?php

namespace App\Services\Trading;

use App\Models\Trade;

class DayTradeDetector
{

    public static function detect($userId)
    {

        $trades = Trade::where('user_id', $userId)
            ->orderBy('trade_date')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($trade) {
                return $trade->trade_date . '_' . $trade->asset;
            });

        foreach ($trades as $group) {

            $buyQty = 0;
            $sellQty = 0;

            foreach ($group as $trade) {

                if ($trade->side === 'buy') {
                    $buyQty += $trade->quantity;
                }

                if ($trade->side === 'sell') {
                    $sellQty += $trade->quantity;
                }

            }

            $dayTradeQty = min($buyQty, $sellQty);

            foreach ($group as $trade) {

                if ($dayTradeQty <= 0) {

                    $trade->trade_type = 'swing';
                    $trade->save();
                    continue;

                }

                if ($trade->side === 'buy') {

                    if ($trade->quantity <= $dayTradeQty) {

                        $trade->trade_type = 'daytrade';
                        $dayTradeQty -= $trade->quantity;

                    } else {

                        $trade->trade_type = 'daytrade';
                        $dayTradeQty = 0;

                    }

                }

                if ($trade->side === 'sell') {

                    if ($trade->quantity <= $dayTradeQty) {

                        $trade->trade_type = 'daytrade';
                        $dayTradeQty -= $trade->quantity;

                    } else {

                        $trade->trade_type = 'swing';

                    }

                }

                $trade->save();

            }

        }

    }

}