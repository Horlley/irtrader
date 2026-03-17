<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Tax\TaxEngine;
use App\Models\TaxResult;
use Illuminate\Support\Facades\DB;
use App\Models\Darf;

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

    public function report()
    {
        $results = DB::table('trades')

            ->selectRaw("

                DATE_FORMAT(trade_date,'%Y-%m') as month,
                CASE
                WHEN asset LIKE 'WIN%' THEN 'indice'
                WHEN asset LIKE 'WDO%' THEN 'dolar'
                END as market,
                SUM(
                CASE
                WHEN side='sell' THEN quantity*price
                ELSE -(quantity*price)
                END
                ) as result

                ")

            ->groupByRaw("

                DATE_FORMAT(trade_date,'%Y-%m'),

                CASE
                WHEN asset LIKE 'WIN%' THEN 'indice'
                WHEN asset LIKE 'WDO%' THEN 'dolar'
                END

                ")

            ->orderBy('month')

            ->get();

        return view('pages.tax_report', compact('results'));
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

        $results = DB::table('tax_results')

            ->selectRaw('
            month,
            profit_daytrade,
            irrf_daytrade,
            tax_due,
            darf_due
        ')

            ->whereYear('month', $year)

            ->orderBy('month')

            ->get();


        $totals = DB::table('tax_results')

            ->selectRaw('
            SUM(profit_daytrade) as total_profit,
            SUM(irrf_daytrade) as total_irrf,
            SUM(tax_due) as total_tax,
            SUM(darf_due) as total_darf
        ')

            ->whereYear('month', $year)

            ->first();


        return view('pages.tax_annual', compact('results', 'totals', 'year'));
    }
}
