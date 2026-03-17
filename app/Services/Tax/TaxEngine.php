<?php

namespace App\Services\Tax;

use App\Models\Import;
use App\Models\TaxResult;
use App\Models\TaxConfig;
use App\Services\Darf\DarfGenerator;

class TaxEngine
{

    public static function calculateYear($userId, $year)
    {

        // pega configuração fiscal
        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        $lossCarry = $config->initial_loss_daytrade ?? 0;
        $irrfCarry = $config->initial_irrf_daytrade ?? 0;


        $imports = Import::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->orderBy('trade_date', 'desc')
            ->get()
            ->groupBy(function ($i) {

                return date('Y-m', strtotime($i->trade_date));
            });


        foreach ($imports as $month => $items) {

            // resultado do mês
            $profit = $items->sum('net_total');

            // IRRF do mês
            $irrf = $items->sum('irrf_daytrade_proj');


            // base considerando prejuízo acumulado
            $base = $profit - $lossCarry;

            if ($base < 0) {

                $lossCarry = abs($base);
                $base = 0;
                $tax = 0;
                $darf = 0;

            } else {

                $tax = $base * 0.20;

                $darf = $tax - ($irrf + $irrfCarry);

                if ($darf < 0) {
                    $irrfCarry = abs($darf);
                    $darf = 0;
                } else {
                    $irrfCarry = 0;
                }

                $lossCarry = 0;

            }


            TaxResult::updateOrCreate(

                [
                    'user_id' => $userId,
                    'month' => $month
                ],

                [
                    'profit_daytrade' => $profit,
                    'irrf_daytrade' => $irrf,
                    'loss_carry_forward' => $lossCarry,
                    'taxable_base' => $base,
                    'tax_due' => $tax,
                    'darf_due' => $darf
                ]

            );

        }

    }

}