<?php

namespace App\Services\Trading;

class TradeCalculatorService
{
    public static function calculateByImport($trades)
    {
        $result = [
            'dolar' => 0,
            'indice' => 0,
            'outros' => 0,
        ];

        $groupedTrades = $trades
            ->sortBy('trade_date')
            ->groupBy(function ($trade) {
                return strtoupper(trim($trade->asset));
            });

        foreach ($groupedTrades as $asset => $assetTrades) {
            $fifo = [];

            foreach ($assetTrades->values() as $trade) {
                $side = strtolower($trade->side);
                $price = (float) $trade->price;
                $qty = (int) $trade->quantity;

                if ($side === 'buy') {
                    $fifo[] = [
                        'price' => $price,
                        'qty' => $qty,
                    ];

                    continue;
                }

                if ($side !== 'sell') {
                    continue;
                }

                $sellQty = $qty;

                while ($sellQty > 0 && !empty($fifo)) {
                    $buy = &$fifo[0];
                    $matchQty = min($buy['qty'], $sellQty);

                    if ($matchQty <= 0) {
                        break;
                    }

                    $points = $price - $buy['price'];
                    $profit = $points * $matchQty * self::getMultiplier($asset);

                    if (str_contains($asset, 'WDO')) {
                        $result['dolar'] += $profit;
                    } elseif (str_contains($asset, 'WIN')) {
                        $result['indice'] += $profit;
                    } else {
                        $result['outros'] += $profit;
                    }

                    $buy['qty'] -= $matchQty;
                    $sellQty -= $matchQty;

                    if ($buy['qty'] <= 0) {
                        array_shift($fifo);
                    }
                }
            }
        }

        return $result;
    }

    private static function getMultiplier($asset)
    {
        if (str_contains($asset, 'WDO')) {
            return 10;
        }

        if (str_contains($asset, 'WIN')) {
            return 0.2;
        }

        return 1;
    }
}
