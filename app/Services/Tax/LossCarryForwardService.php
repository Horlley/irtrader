<?php

namespace App\Services\Tax;

use App\Models\MonthlyResult;

class LossCarryForwardService
{

    public static function apply($userId, $year, $month, $profitDaytrade, $profitSwing, $profitFuturo)
    {

        $previous = MonthlyResult::where('user_id', $userId)
            ->where(function ($q) use ($year, $month) {

                $q->where('year', '<', $year)
                  ->orWhere(function ($q2) use ($year, $month) {
                      $q2->where('year', $year)
                         ->where('month', '<', $month);
                  });

            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $carryDaytrade = $previous->carry_loss_daytrade ?? 0;
        $carrySwing = $previous->carry_loss_swing ?? 0;
        $carryFuturo = $previous->carry_loss_futuro ?? 0;

        /*
        |--------------------------------
        | DAY TRADE
        |--------------------------------
        */

        if ($profitDaytrade > 0 && $carryDaytrade < 0) {

            $offset = min($profitDaytrade, abs($carryDaytrade));

            $profitDaytrade -= $offset;
            $carryDaytrade += $offset;

        } elseif ($profitDaytrade < 0) {

            $carryDaytrade += $profitDaytrade;

        }

        /*
        |--------------------------------
        | SWING
        |--------------------------------
        */

        if ($profitSwing > 0 && $carrySwing < 0) {

            $offset = min($profitSwing, abs($carrySwing));

            $profitSwing -= $offset;
            $carrySwing += $offset;

        } elseif ($profitSwing < 0) {

            $carrySwing += $profitSwing;

        }

        /*
        |--------------------------------
        | FUTURO
        |--------------------------------
        */

        if ($profitFuturo > 0 && $carryFuturo < 0) {

            $offset = min($profitFuturo, abs($carryFuturo));

            $profitFuturo -= $offset;
            $carryFuturo += $offset;

        } elseif ($profitFuturo < 0) {

            $carryFuturo += $profitFuturo;

        }

        return [

            'profit_daytrade' => $profitDaytrade,
            'profit_swing' => $profitSwing,
            'profit_futuro' => $profitFuturo,

            'carry_daytrade' => $carryDaytrade,
            'carry_swing' => $carrySwing,
            'carry_futuro' => $carryFuturo

        ];

    }

}