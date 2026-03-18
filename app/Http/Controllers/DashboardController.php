<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonthlyResult;

class DashboardController extends Controller
{

    public function stats()
{
    $userId = 1;
    $year = date('Y');

    $data = \App\Services\Trading\AnnualTaxService::calculate($userId, $year);

    // 🔥 pega último mês com movimento
    $lastMonth = collect($data['months'])
        ->reverse()
        ->first(function ($m) {
            return abs($m['result']) > 0.0001;
        });

    if (!$lastMonth) {
        return [
            'profit_month' => 0,
            'tax_due' => 0,
            'loss_carry' => 0,
            'darf_pending' => 0,
            'markets' => [
                'dolar' => 0,
                'indice' => 0,
                'outros' => 0
            ]
        ];
    }

    return [
        'profit_month' => $lastMonth['result'],
        'tax_due' => $lastMonth['tax'],
        'loss_carry' => $lastMonth['loss_carry'],
        'darf_pending' => $lastMonth['darf'],

        'markets' => [
            'dolar' => $lastMonth['markets']['dolar']['profit'] ?? 0,
            'indice' => $lastMonth['markets']['indice']['profit'] ?? 0,
            'outros' => $lastMonth['markets']['outros']['profit'] ?? 0,
        ]
    ];
}

    public function chart()
    {
        $userId = 1;
        $year = date('Y');

        $data = \App\Services\Trading\AnnualTaxService::calculate($userId, $year);

        $labels = [];
        $values = [];

        foreach ($data['months'] as $m) {

            $labels[] = $m['month'] . '/' . $year;
            $values[] = $m['result'];
        }

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
