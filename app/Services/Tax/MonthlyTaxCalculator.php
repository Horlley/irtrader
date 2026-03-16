<?php

namespace App\Services\Tax;

use App\Models\Trade;
use App\Models\MonthlyResult;
use App\Services\Tax\LossCarryForwardService;

class MonthlyTaxCalculator
{

    public static function calculate($userId, $year, $month)
    {

        $trades = Trade::where('user_id', $userId)
            ->whereYear('trade_date', $year)
            ->whereMonth('trade_date', $month)
            ->where('side', 'sell')
            ->get();

        $profitDaytrade = 0;
        $profitSwing = 0;
        $profitFuturo = 0;

        $totalSales = 0;

        foreach ($trades as $trade) {

            $profit = $trade->profit ?? 0;

            $totalSales += $trade->sell_value ?? 0;

            /*
            |--------------------------------
            | MERCADO FUTURO
            |--------------------------------
            */

            if ($trade->market === 'futuro') {

                $profitFuturo += $profit;
                continue;

            }

            /*
            |--------------------------------
            | DAY TRADE
            |--------------------------------
            */

            if ($trade->trade_type === 'daytrade') {

                $profitDaytrade += $profit;
                continue;

            }

            /*
            |--------------------------------
            | SWING TRADE
            |--------------------------------
            */

            if ($trade->trade_type === 'swing') {

                $profitSwing += $profit;

            }

        }

        /*
        |--------------------------------
        | REGRA DOS 20K (ações)
        |--------------------------------
        */

        if ($totalSales <= 20000) {
            $profitSwing = 0;
        }

        /*
        |--------------------------------
        | COMPENSA PREJUÍZO
        |--------------------------------
        */

        $loss = LossCarryForwardService::apply(
            $userId,
            $year,
            $month,
            $profitDaytrade,
            $profitSwing,
            $profitFuturo
        );

        $profitDaytrade = $loss['profit_daytrade'];
        $profitSwing = $loss['profit_swing'];
        $profitFuturo = $loss['profit_futuro'];

        $carryDaytrade = $loss['carry_daytrade'];
        $carrySwing = $loss['carry_swing'];
        $carryFuturo = $loss['carry_futuro'];

        /*
        |--------------------------------
        | CALCULO DO IMPOSTO
        |--------------------------------
        */

        $taxDaytrade = $profitDaytrade > 0 ? $profitDaytrade * 0.20 : 0;
        $taxSwing = $profitSwing > 0 ? $profitSwing * 0.15 : 0;
        $taxFuturo = $profitFuturo > 0 ? $profitFuturo * 0.15 : 0;

        $taxDue = $taxDaytrade + $taxSwing + $taxFuturo;

        /*
        |--------------------------------
        | SALVA RESULTADO
        |--------------------------------
        */

        return MonthlyResult::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month
            ],
            [

                'profit_daytrade' => $profitDaytrade,
                'profit_swing' => $profitSwing,
                'profit_futuro' => $profitFuturo,

                'carry_loss_daytrade' => $carryDaytrade,
                'carry_loss_swing' => $carrySwing,
                'carry_loss_futuro' => $carryFuturo,

                'total_sales' => $totalSales,

                'tax_daytrade' => $taxDaytrade,
                'tax_swing' => $taxSwing,
                'tax_futuro' => $taxFuturo,

                'tax_due' => $taxDue
            ]
        );

    }

}