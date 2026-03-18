<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\DB;

class MarketClassifier
{
    public static function classifyFromTrades($trades)
    {
        $markets = [
            'dolar' => 0,
            'indice' => 0,
            'outros' => 0,
        ];

        if (empty($trades) || !is_array($trades)) {
            return $markets;
        }

        foreach ($trades as $trade) {

            if (!is_array($trade)) {
                continue;
            }

            $assetRaw = $trade['asset'] ?? '';
            $asset = strtoupper(trim((string) $assetRaw));
            $asset = preg_replace('/[^A-Z0-9]/', '', $asset);

            $result = isset($trade['result']) ? (float) $trade['result'] : 0;

            if (preg_match('/WDO|DOL/i', $asset)) {

                $markets['dolar'] += $result;

            } elseif (preg_match('/WIN|IND/i', $asset)) {

                $markets['indice'] += $result;

            } else {

                $markets['outros'] += $result;
            }
        }

        return $markets;
    }

    public static function classifyFromItem($item)
    {
        // 🔥 busca trades reais do import
        $trades = DB::table('trades')
            ->where('import_id', $item->id)
            ->get();

        if ($trades->isEmpty()) {
            return [
                'dolar' => 0,
                'indice' => 0,
                'outros' => (float) $item->net_total,
            ];
        }

        $markets = [
            'dolar' => 0,
            'indice' => 0,
            'outros' => 0,
        ];

        $count = [
            'dolar' => 0,
            'indice' => 0,
            'outros' => 0,
        ];

        foreach ($trades as $trade) {

            $market = strtolower(trim((string) ($trade->market ?? '')));
            $asset = strtoupper(trim((string) ($trade->asset ?? '')));

            if (
                str_contains($market, 'dol') ||
                str_contains($asset, 'WDO')
            ) {
                $count['dolar']++;

            } elseif (
                str_contains($market, 'ind') ||
                str_contains($asset, 'WIN')
            ) {
                $count['indice']++;

            } else {
                $count['outros']++;
            }
        }

        $totalTrades = array_sum($count);

        if ($totalTrades == 0) {
            return [
                'dolar' => 0,
                'indice' => 0,
                'outros' => (float) $item->net_total,
            ];
        }

        // 🔥 distribuição proporcional
        foreach ($count as $key => $qty) {

            if ($qty > 0) {
                $markets[$key] = ($qty / $totalTrades) * $item->net_total;
            }
        }

        return $markets;
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