<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Tax\TaxEngine;
use App\Models\TaxResult;
use App\Models\Darf;
use App\Services\Trading\AnnualTaxService;
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

    // 🔥 RELATÓRIO MENSAL (CORRIGIDO)
    public function report(Request $request)
    {
        $userId = Auth::id() ?? 1;

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // 🔥 calcula o ano inteiro
        $result = AnnualTaxService::calculate($userId, $year);

        // 🔥 pega apenas o mês selecionado
        $selectedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        $monthData = collect($result['months'])
            ->firstWhere('month', $selectedMonth);

        // 🔥 total agora é do mês (CORREÇÃO PRINCIPAL)
        $total = $monthData['result'] ?? 0;

        return view('pages.tax_report', [
            'months' => $result['months'], // usado na view
            'total' => $total,             // 🔥 corrigido
            'year' => $year,
            'month' => $month
        ]);
    }

    public function calculate()
    {
        TaxEngine::calculateYear(1, date('Y'));

        return redirect()
            ->route('tax.index')
            ->with('success', 'Apuração recalculada');
    }

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

    public function reportIr($year)
    {
        $userId = Auth::id() ?? 1;

        $data = \App\Services\Trading\AnnualTaxService::calculate($userId, $year);

        return view('pages.tax_report_ir', [
            'months' => $data['months'],
            'year' => $year
        ]);
    }
}
