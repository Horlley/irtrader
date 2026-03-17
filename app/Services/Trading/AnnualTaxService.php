<?php

namespace App\Services\Trading;

class AnnualTaxService
{

    public static function calculate($userId, $year)
    {

        $months = [];
        $lossCarry = 0;

        $totalProfit = 0;
        $totalTax = 0;
        $totalIrrf = 0;

        for ($m = 1; $m <= 12; $m++) {

            $monthly = MonthlyMarketResultService::calculate($userId, $year, $m);

            $result = collect($monthly)->sum('result');

            // 🔹 aplica prejuízo acumulado
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

            // 🔹 IRRF fake (depois vamos puxar do import)
            $irrf = abs($result) * 0.0001;

            $darf = max(0, $tax - $irrf);

            $months[] = [
                'month' => str_pad($m, 2, '0', STR_PAD_LEFT),
                'result' => $result,
                'base' => $baseCalc,
                'tax' => $tax,
                'irrf' => $irrf,
                'darf' => $darf
            ];

            $totalProfit += $result;
            $totalTax += $tax;
            $totalIrrf += $irrf;
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