<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonthlyResult;

class DashboardController extends Controller
{

    public function stats()
    {

        $last = MonthlyResult::orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (!$last) {

            return [

                'profit_month' => 0,
                'tax_due' => 0,
                'loss_carry' => 0,
                'darf_pending' => 0

            ];
        }

        return [

            'profit_month' => $last->profit_daytrade
                + $last->profit_swing
                + $last->profit_futuro,

            'tax_due' => $last->tax_due,

            'loss_carry' =>
            $last->carry_loss_daytrade
                + $last->carry_loss_swing
                + $last->carry_loss_futuro,

            'darf_pending' => $last->darf_value ?? 0

        ];
    }

    public function chart()
    {

        $data = MonthlyResult::orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = $data->map(function ($m) {

            return sprintf('%02d/%s', $m->month, $m->year);
        });

        $values = $data->map(function ($m) {

            return
                $m->profit_daytrade +
                $m->profit_swing +
                $m->profit_futuro;
        });

        return [

            'labels' => $labels,
            'data' => $values

        ];
    }

    public function create()
    {
        return view('trades.create');
    }
}
