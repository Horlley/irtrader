<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Tax\TaxEngine;
use App\Models\TaxResult;
use Illuminate\Support\Facades\DB;
use App\Models\Darf;
use App\Services\Trading\AnnualTaxService;
use App\Services\Trading\MonthlyMarketResultService;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{

    public function index()
    {
        $results = TaxResult::orderBy('month')->get();

        return view('pages.tax', compact('results'));
    }

    public function darfs()
    {
        $darfs = Darf::orderBy('month', 'desc')->get();

        return view('pages.darfs', compact('darfs'));
    }

    // 🔥 RELATÓRIO MENSAL (AGORA USANDO SERVICE CORRETO)
    public function report(Request $request)
    {
        $userId = Auth::id() ?? 1;

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $data = MonthlyMarketResultService::calculate($userId, $year, $month);

        $total = collect($data)->sum('result');

        return view('pages.tax_report', [
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'total' => $total
        ]);
    }

    public function calculate()
    {
        TaxEngine::calculateYear(1, date('Y'));

        return redirect()
            ->route('tax.index')
            ->with('success', 'Apuração recalculada');
    }

    // 🔥 RELATÓRIO ANUAL (AGORA DINÂMICO)
    public function annual($year)
    {
        $userId = Auth::id() ?? 1;

        $data = AnnualTaxService::calculate($userId, $year);

        return view('pages.tax_annual', [
            'months' => $data['months'],
            'summary' => $data['summary'],
            'year' => $year
        ]);
    }
}