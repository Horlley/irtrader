<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Trading\TradeCalculator;

class ReportController extends Controller
{

    public function monthly()
    {

        $year = now()->year;
        $month = now()->month;

        $result = TradeCalculator::monthlyResult(1, $year, $month);

        return view('report.monthly', compact('result'));

    }

}