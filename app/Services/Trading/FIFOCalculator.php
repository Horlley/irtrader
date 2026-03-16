<?php

namespace App\Services\Trading;

use App\Models\Trade;

class FIFOCalculator
{

    public static function calculate($userId)
    {

        $trades = Trade::where('user_id', $userId)
            ->orderBy('trade_date')
            ->orderBy('id')
            ->get();

        $positions = [];

        foreach ($trades as $trade) {

            $asset = $trade->asset;

            if (!isset($positions[$asset])) {
                $positions[$asset] = [];
            }

            /*
            |----------------------------------------
            | COMPRA
            |----------------------------------------
            */

            if ($trade->side === 'buy') {

                $positions[$asset][] = [
                    'quantity' => $trade->quantity,
                    'price' => $trade->price
                ];

            }

            /*
            |----------------------------------------
            | VENDA
            |----------------------------------------
            */

            if ($trade->side === 'sell') {

                $sellQty = $trade->quantity;
                $sellPrice = $trade->price;

                $costBasis = 0;

                while ($sellQty > 0 && count($positions[$asset]) > 0) {

                    $buy = &$positions[$asset][0];

                    $matchedQty = min($sellQty, $buy['quantity']);

                    $costBasis += $buy['price'] * $matchedQty;

                    $buy['quantity'] -= $matchedQty;
                    $sellQty -= $matchedQty;

                    if ($buy['quantity'] == 0) {
                        array_shift($positions[$asset]);
                    }

                }

                $sellValue = $trade->quantity * $sellPrice;

                $fees = $trade->fees ?? 0;

                $profit = $sellValue - $costBasis - $fees;

                /*
                |----------------------------------------
                | SALVA RESULTADO
                |----------------------------------------
                */

                $trade->sell_value = $sellValue;
                $trade->cost_basis = $costBasis;
                $trade->profit = $profit;

                $trade->save();

            }

        }

    }

}