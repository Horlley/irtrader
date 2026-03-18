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

        // 🔥 ordena corretamente
        $trades = $trades->sortBy('trade_date')->values();

        $fifo = [];

        foreach ($trades as $trade) {

            $asset = strtoupper(trim($trade->asset));
            $side = strtolower($trade->side);
            $price = (float) $trade->price;
            $qty = (int) $trade->quantity;

            if ($side === 'buy') {

                $fifo[] = [
                    'asset' => $asset,
                    'price' => $price,
                    'qty' => $qty,
                ];

            } elseif ($side === 'sell') {

                $sellQty = $qty;

                while ($sellQty > 0 && !empty($fifo)) {

                    $buy = &$fifo[0];

                    // 🔥 só casa MESMO ATIVO (sem destruir fila)
                    if ($buy['asset'] !== $asset) {
                        break;
                    }

                    $matchQty = min($buy['qty'], $sellQty);

                    if ($matchQty <= 0) break;

                    $points = $price - $buy['price'];

                    // 🔥 AQUI ESTAVA O ERRO
                    $mult = self::getMultiplier($asset);
                    $profit = $points * $matchQty * $mult;

                    if (str_contains($asset, 'WDO')) {
                        $result['dolar'] += $profit;

                    } elseif (str_contains($asset, 'WIN')) {
                        $result['indice'] += $profit;
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
        if (str_contains($asset, 'WDO')) return 10;   // dólar
        if (str_contains($asset, 'WIN')) return 0.2;  // índice

        return 1;
    }
}