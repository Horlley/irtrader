<?php

namespace App\Services\Trading;

use App\Models\TaxConfig;

class AnnualTaxService
{

    public static function calculate($userId, $year)
    {

        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        $lossCarry = $config->initial_loss_daytrade ?? 0;
        $irrfCarry = $config->initial_irrf_daytrade ?? 0;

        $months = [];

        $totalProfit = 0;
        $totalTax = 0;
        $totalIrrf = 0;

        for ($m = 1; $m <= 12; $m++) {

            $monthly = MonthlyMarketResultService::calculate($userId, $year, $m);

            $result = collect($monthly)->sum('result');

            // 🔹 prejuízo anterior
            $previousLoss = $lossCarry;

            // 🔹 aplica compensação
            $base = $result + $lossCarry;

            if ($base < 0) {

                $lossCarry = $base;
                $tax = 0;
                $baseCalc = 0;

            } else {

                $tax = $base * 0.20;
                $baseCalc = $base;
                $lossCarry = 0;
            }

            // 🔹 IRRF do mês (placeholder)
            $irrfMonth = abs($result) * 0.0001;

            // 🔹 acumula IRRF
            $irrfCarry += $irrfMonth;

            // 🔥 calcula quanto IRRF pode usar
            $irrfUsed = min($tax, $irrfCarry);

            // 🔥 imposto final
            $darf = $tax - $irrfUsed;

            // 🔥 atualiza saldo de IRRF
            $irrfCarry -= $irrfUsed;

            $months[] = [
                'month' => str_pad($m, 2, '0', STR_PAD_LEFT),

                'result' => $result,

                'previous_loss' => $previousLoss,
                'loss_carry' => $lossCarry,

                'base' => $baseCalc,
                'tax' => $tax,

                'irrf_month' => $irrfMonth,
                'irrf_used' => $irrfUsed,          // 🔥 novo
                'irrf_balance' => $irrfCarry,      // 🔥 novo

                'darf' => $darf
            ];

            $totalProfit += $result;
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