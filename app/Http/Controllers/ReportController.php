<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Trading\TradeCalculator;
use App\Services\Trading\MonthlyMarketResultService;

class ReportController extends Controller
{

    public function monthly()
    {
        $year = request('year', now()->year);
        $month = request('month', now()->month);

        $data = MonthlyMarketResultService::calculate(1, $year, $month);

        return view('reports.monthly', compact('data', 'year', 'month'));
    }
}
