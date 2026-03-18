<?php

namespace App\Services\Trading;

use App\Models\Import;
use App\Models\TaxConfig;

class AnnualTaxService
{
    public static function calculate($userId, $year)
    {
        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        if (!$config) {
            $config = TaxConfig::where('user_id', 0)
                ->where('year', 0)
                ->first();
        }

        // 🔥 PREJUÍZO SEMPRE NEGATIVO
        $lossCarry = -abs($config->initial_loss_daytrade ?? 0);

        // 🔥 IRRF ACUMULADO
        $irrfCarry = abs($config->initial_irrf_daytrade ?? 0);

        $imports = Import::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->get()
            ->groupBy(fn($i) => date('Y-m', strtotime($i->trade_date)));

        $months = [];

        $totalProfit = 0;
        $totalTax = 0;
        $totalIrrf = 0;

        for ($m = 1; $m <= 12; $m++) {

            $monthKey = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $items = $imports->get($monthKey, collect());

            // 🔹 RESULTADO DO MÊS (BASE OFICIAL)
            $profit = $items->sum('net_total');
            $irrfMonth = $items->sum('irrf_daytrade_proj');

            // 🔹 MARKETS (APENAS VISUAL)
            $markets = [
                'vista_acoes' => 0,
                'vista_ouro' => 0,

                'opcoes' => 0,

                'dolar' => 0,
                'indice' => 0,
                'outros' => 0,

                'termo' => 0
            ];

            foreach ($items as $item) {
                $markets['dolar'] += $item->result_dolar ?? 0;
                $markets['indice'] += $item->result_indice ?? 0;
                $markets['outros'] += $item->net_total ?? 0;
            }

            // 🔥 FALLBACK (garante que nunca fica zerado)
            if (($markets['dolar'] + $markets['indice']) == 0 && $profit != 0) {
                $markets['indice'] = $profit;
            }

            $previousLoss = $lossCarry;

            // 🔥 BASE CORRETA
            $base = $profit + $lossCarry;

            if ($base <= 0) {
                $lossCarry = $base;
                $tax = 0;
                $baseCalc = 0;
            } else {
                $tax = $base * 0.20;
                $baseCalc = $base;
                $lossCarry = 0;
            }

            // 🔹 IRRF
            $irrfCarry += $irrfMonth;

            $irrfUsed = min($tax, $irrfCarry);
            $darf = $tax - $irrfUsed;

            $irrfCarry -= $irrfUsed;

            $irrfPrevious = $irrfCarry + $irrfUsed - $irrfMonth;

            // 🔹 MARKET DETAILS (simples por enquanto)
            $marketDetails = [];

            foreach ($markets as $name => $value) {
                $marketDetails[$name] = [
                    'profit' => round($value, 2),
                    'tax' => 0,
                    'net' => round($value, 2)
                ];
            }

            $months[] = [
                'month' => str_pad($m, 2, '0', STR_PAD_LEFT),

                'result' => round($profit, 2),

                'previous_loss' => round($previousLoss, 2),
                'loss_carry' => round($lossCarry, 2),

                'base' => round($baseCalc, 2),
                'tax' => round($tax, 2),

                'irrf_month' => round($irrfMonth, 2),
                'irrf_used' => round($irrfUsed, 2),
                'irrf_balance' => round($irrfCarry, 2),

                'irrf_previous' => round($irrfPrevious, 2),

                'darf' => round($darf, 2),

                'markets' => $marketDetails
            ];

            $totalProfit += $profit;
            $totalTax += $tax;
            $totalIrrf += $irrfMonth;
        }

        return [
            'months' => $months,
            'summary' => [
                'profit' => round($totalProfit, 2),
                'irrf' => round($totalIrrf, 2),
                'tax' => round($totalTax, 2),
                'darf' => round(max(0, $totalTax - $totalIrrf), 2)
            ]
        ];
    }
}
