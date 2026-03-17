<?php

namespace App\Services\Trading;

use App\Models\Import;
use App\Models\TaxConfig;

class AnnualTaxService
{

    public static function calculate($userId, $year)
    {

        // 🔥 busca config do usuário
        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        // 🔥 fallback global (config padrão)
        if (!$config) {
            $config = TaxConfig::where('user_id', 0)
                ->where('year', 0)
                ->first();
        }

        // 🔥 valores iniciais
        $lossCarry = $config->initial_loss_daytrade ?? 0;
        $irrfCarry = $config->initial_irrf_daytrade ?? 0;

        // 🔥 normalizações IMPORTANTES
        $lossCarry = -abs($lossCarry); // prejuízo sempre negativo
        $irrfCarry = abs($irrfCarry);  // IRRF sempre positivo

        // 🔥 busca imports agrupados por mês
        $imports = Import::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->get()
            ->groupBy(function ($i) {
                return date('Y-m', strtotime($i->trade_date));
            });

        $months = [];

        $totalProfit = 0;
        $totalTax = 0;
        $totalIrrf = 0;

        for ($m = 1; $m <= 12; $m++) {

            $monthKey = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);

            $items = $imports[$monthKey] ?? collect();

            // 🔹 resultado e IRRF do mês (dados reais da nota)
            $profit = $items->sum('net_total');
            $irrfMonth = $items->sum('irrf_daytrade_proj');

            // 🔹 guarda prejuízo anterior
            $previousLoss = $lossCarry;

            // 🔹 aplica compensação
            $base = $profit + $lossCarry;

            if ($base < 0) {

                // continua acumulando prejuízo
                $lossCarry = $base;
                $tax = 0;
                $baseCalc = 0;

            } else {

                $tax = $base * 0.20;
                $baseCalc = $base;

                // zerou prejuízo
                $lossCarry = 0;
            }

            // 🔥 IRRF acumulado real
            $irrfCarry += $irrfMonth;

            // 🔥 quanto pode usar de IRRF
            $irrfUsed = min($tax, $irrfCarry);

            // 🔥 DARF final
            $darf = $tax - $irrfUsed;

            // 🔥 atualiza saldo IRRF
            $irrfCarry -= $irrfUsed;

            $months[] = [
                'month' => str_pad($m, 2, '0', STR_PAD_LEFT),

                'result' => $profit,

                'previous_loss' => $previousLoss,
                'loss_carry' => $lossCarry,

                'base' => $baseCalc,
                'tax' => $tax,

                'irrf_month' => $irrfMonth,
                'irrf_used' => $irrfUsed,
                'irrf_balance' => $irrfCarry,

                'darf' => $darf
            ];

            $totalProfit += $profit;
            $totalTax += $tax;
            $totalIrrf += $irrfMonth;
        }

        return [
            'months' => $months,
            'summary' => [
                'profit' => $totalProfit,
                'irrf' => $totalIrrf,
                'tax' => $totalTax,
                'darf' => max(0, $totalTax - $totalIrrf)
            ]
        ];
    }
}