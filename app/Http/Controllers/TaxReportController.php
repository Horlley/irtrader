<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Trading\MonthlyMarketResultService;
use Illuminate\Support\Facades\Auth;

class TaxReportController extends Controller
{

    public function index(Request $request)
    {
        $userId = Auth::id() ?? 1;

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = MonthlyMarketResultService::calculate($userId, $year, $month);

        // 🔥 ESSA LINHA É OBRIGATÓRIA
        $total = collect($data)->sum('result');

        return view('pages.tax_report', [
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'total' => $total // 🔥 ESSA TAMBÉM
        ]);
    }

}