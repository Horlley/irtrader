<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\DB;

class MarketClassifier
{
    public static function classifyFromItem($item)
    {
        // 🔥 busca trades do import
        $trades = DB::table('trades')
            ->where('import_id', $item->id)
            ->orderBy('id')
            ->get();

        if ($trades->isEmpty()) {
            return [
                'dolar' => 0,
                'indice' => 0,
                'outros' => (float) $item->net_total,
            ];
        }

        // 🔥 agrupa por ativo
        $grouped = [];

        foreach ($trades as $trade) {
            $asset = strtoupper(trim($trade->asset));
            $grouped[$asset][] = $trade;
        }

        $result = [
            'dolar' => 0,
            'indice' => 0,
            'outros' => 0,
        ];

        foreach ($grouped as $asset => $assetTrades) {

            $fifo = [];

            foreach ($assetTrades as $trade) {

                $side = strtolower($trade->side);
                $price = (float) $trade->price;
                $qty = (int) $trade->quantity;

                if ($side === 'buy') {

                    $fifo[] = [
                        'price' => $price,
                        'qty' => $qty,
                    ];
                } elseif ($side === 'sell') {

                    $sellQty = $qty;

                    while ($sellQty > 0 && !empty($fifo)) {

                        $buy = &$fifo[0];

                        $matchQty = min($buy['qty'], $sellQty);

                        // 🔥 ignora se não tem match real
                        if ($matchQty <= 0) {
                            break;
                        }

                        $points = $price - $buy['price'];

                        $mult = self::getMultiplier($asset);

                        $profit = $points * $matchQty * $mult;

                        // 🔥 classifica corretamente
                        if (str_contains($asset, 'WDO')) {

                            $result['dolar'] += $profit;
                        } elseif (str_contains($asset, 'WIN')) {

                            $result['indice'] += $profit;
                        }

                        // 🔥 REMOVE OUTROS (CRÍTICO)
                        // não usa mais 'outros' aqui

                        $buy['qty'] -= $matchQty;
                        $sellQty -= $matchQty;

                        if ($buy['qty'] <= 0) {
                            array_shift($fifo);
                        }
                    }
                }
            }
        }

        return $result;
    }

    private static function getMultiplier($asset)
    {
        if (str_contains($asset, 'WDO')) return 10;
        if (str_contains($asset, 'WIN')) return 0.2;

        return 1;
    }

    public static function normalizeTotals(array $markets, $expectedTotal)
    {
        $expectedTotal = (float) $expectedTotal;

        $total = array_sum($markets);

        if (abs($total - $expectedTotal) > 0.01) {
            $markets['outros'] += ($expectedTotal - $total);
        }

        return $markets;
    }
}
