<?php

namespace App\Services\Trading;

use App\Models\Import;
use App\Models\TaxConfig;
use App\Services\Trading\TradeCalculatorService;
use Illuminate\Support\Facades\DB;

class AnnualTaxService
{
    public static function calculate($userId, $year)
    {
        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first()
            ?? TaxConfig::where('user_id', 0)->where('year', 0)->first();

        // 🔥 ESTADO INICIAL
        $lossCarry = -abs($config->initial_loss_daytrade ?? 0);
        $irrfCarry = abs($config->initial_irrf_daytrade ?? 0);

        $imports = Import::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->get()
            ->groupBy(fn($i) => date('Y-m', strtotime($i->trade_date)));

        $months = [];

        $totalProfit = 0;
        $totalTax = 0;
        $totalIrrf = 0;
        $totalDarf = 0;

        for ($m = 1; $m <= 12; $m++) {

            $monthKey = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $items = $imports->get($monthKey, collect());

            $profit = $items->sum('net_total'); // 🔥 já líquido
            $irrfMonth = $items->sum('irrf_daytrade_proj');

            // =========================
            // 🔹 MARKET BREAKDOWN (BRUTO via FIFO)
            // =========================
            $markets = [
                'dolar' => 0,
                'indice' => 0,
                'outros' => 0,
            ];

            foreach ($items as $item) {

                $trades = DB::table('trades')
                    ->where('import_id', $item->id)
                    ->orderBy('trade_date')
                    ->get();

                if ($trades->isEmpty()) {
                    continue;
                }

                $itemMarkets = TradeCalculatorService::calculateByImport($trades);

                $markets['dolar'] += $itemMarkets['dolar'];
                $markets['indice'] += $itemMarkets['indice'];
                $markets['outros'] += $itemMarkets['outros'];
            }

            // =========================
            // 🔥 DISTRIBUIÇÃO LÍQUIDA (CORREÇÃO PRINCIPAL)
            // =========================
            $totalBruto = $markets['dolar'] + $markets['indice'];

            if ($totalBruto != 0) {

                $ratioDolar = $markets['dolar'] / $totalBruto;
                $ratioIndice = $markets['indice'] / $totalBruto;

                $markets['dolar'] = $profit * $ratioDolar;
                $markets['indice'] = $profit * $ratioIndice;
                $markets['outros'] = 0;

            } else {
                // fallback seguro
                $markets['outros'] = $profit;
            }

            // =========================
            // 🔹 BASE DE CÁLCULO
            // =========================
            $previousLoss = $lossCarry;

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

            // =========================
            // 🔹 IRRF
            // =========================
            $irrfPrevious = $irrfCarry;

            $irrfCarry += $irrfMonth;

            $irrfUsed = min($tax, $irrfCarry);

            $irrfCarry -= $irrfUsed;

            $darf = max(0, $tax - $irrfUsed);

            // =========================
            // 🔹 MARKET DETAILS
            // =========================
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

                'irrf_previous' => round($irrfPrevious, 2),
                'irrf_month' => round($irrfMonth, 2),
                'irrf_used' => round($irrfUsed, 2),
                'irrf_balance' => round($irrfCarry, 2),

                'darf' => round($darf, 2),

                'markets' => $marketDetails
            ];

            $totalProfit += $profit;
            $totalTax += $tax;
            $totalIrrf += $irrfMonth;
            $totalDarf += $darf;
        }

        return [
            'months' => $months,
            'summary' => [
                'profit' => round($totalProfit, 2),
                'irrf' => round($totalIrrf, 2),
                'tax' => round($totalTax, 2),
                'darf' => round($totalDarf, 2)
            ]
        ];
    }
}