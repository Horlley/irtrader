<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Tax\TaxEngine;
use App\Models\TaxResult;
use App\Models\Darf;
use App\Models\Import;
use App\Models\Trade;
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

    public function brokerageNotes(Request $request)
    {
        $userId = Auth::id() ?? 1;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        $query = Import::with('trades')
            ->where('user_id', $userId)
            ->whereYear('trade_date', $year);

        if ($month) {
            $query->whereMonth('trade_date', $month);
        }

        if ($request->filled('number')) {
            $query->where('note_number', 'like', '%' . $request->number . '%');
        }

        if ($request->filled('broker')) {
            $query->where('broker', $request->broker);
        }

        if ($request->filled('asset')) {
            $query->whereHas('trades', function ($q) use ($request) {
                $q->where('asset', $request->asset);
            });
        }

        if ($request->filled('origin')) {
            $query->whereHas('trades', function ($q) use ($request) {
                $q->where('market', $request->origin);
            });
        }

        if ($request->filled('period')) {
            $period = trim($request->period);

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $period)) {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $period)->format('Y-m-d');
                $query->whereDate('trade_date', $date);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
                [$periodYear, $periodMonth] = explode('-', $period);
                $query->whereYear('trade_date', $periodYear)
                    ->whereMonth('trade_date', $periodMonth);
            }
        }

        $notes = $query
            ->orderBy('trade_date')
            ->get();

        $summary = [
            'notes_count' => $notes->count(),
            'registration_fee' => $notes->sum('bmf_registration_fee'),
            'bmf_fees' => $notes->sum('bmf_fees'),
            'ir_day_trade' => $notes->sum(function ($note) {
                return $note->irrf_daytrade_proj ?? $note->irrf_daytrade ?? 0;
            }),
            'net_total' => $notes->sum(function ($note) {
                return $note->net_total ?? $note->net_result ?? 0;
            }),
        ];

        $years = Import::where('user_id', $userId)
            ->selectRaw('YEAR(trade_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([$year]);
        }

        $brokers = Import::where('user_id', $userId)
            ->select('broker')
            ->distinct()
            ->orderBy('broker')
            ->pluck('broker');

        $assets = Trade::where('user_id', $userId)
            ->select('asset')
            ->distinct()
            ->orderBy('asset')
            ->pluck('asset');

        $origins = Trade::where('user_id', $userId)
            ->select('market')
            ->distinct()
            ->orderBy('market')
            ->pluck('market');

        return view('pages.brokerage_notes', [
            'notes' => $notes,
            'years' => $years,
            'year' => $year,
            'month' => $month,
            'brokers' => $brokers,
            'assets' => $assets,
            'origins' => $origins,
            'summary' => $summary,
        ]);
    }

    // 🔥 RELATÓRIO MENSAL (CORRIGIDO)
    public function updateBrokerageNote(Request $request, Import $import)
    {
        $userId = Auth::id() ?? 1;

        if ((int) $import->user_id !== (int) $userId) {
            abort(403);
        }

        $import->update([
            'operational_fee' => $this->moneyInput($request->input('operational_fee')),
            'total_costs' => $this->moneyInput($request->input('total_costs')),
            'bmf_registration_fee' => $this->moneyInput($request->input('bmf_registration_fee')),
            'bmf_fees' => $this->moneyInput($request->input('bmf_fees')),
            'irrf_daytrade' => $this->moneyInput($request->input('irrf')),
            'irrf_daytrade_proj' => $this->moneyInput($request->input('irrf_daytrade_proj')),
            'net_total' => $this->moneyInput($request->input('net_total')),
        ]);

        return redirect()
            ->route('tax.brokerage-notes', $request->only([
                'year',
                'month',
                'number',
                'broker',
                'asset',
                'origin',
                'period',
            ]))
            ->with('success', 'Nota atualizada com sucesso');
    }

    public function brokerageNoteOperations(Request $request, Import $import)
    {
        $userId = Auth::id() ?? 1;

        if ((int) $import->user_id !== (int) $userId) {
            abort(403);
        }

        $import->load(['trades' => function ($query) {
            $query->orderBy('id');
        }]);

        $summary = $import->trades
            ->groupBy('asset')
            ->map(function ($items, $asset) {
                $buys = $items->where('side', 'buy')->sum('quantity');
                $sells = $items->where('side', 'sell')->sum('quantity');

                return [
                    'asset' => $asset,
                    'buys' => $buys,
                    'sells' => $sells,
                    'net' => $buys - $sells,
                ];
            })
            ->values();

        $backUrl = $request->get('back') ?: route('tax.brokerage-notes', [
            'year' => \Carbon\Carbon::parse($import->trade_date)->format('Y'),
        ]);

        return view('pages.brokerage_note_operations', [
            'note' => $import,
            'summary' => $summary,
            'backUrl' => $backUrl,
        ]);
    }

    private function moneyInput($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = str_replace('R$', '', $value);
        $value = str_replace(' ', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

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

        if ($request->expectsJson()) {
            return response()->json([
                'months' => $result['months'],
                'monthData' => $monthData,
                'total' => round($total, 2),
                'year' => (int) $year,
                'month' => (int) $month,
            ]);
        }

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

    public function reportIr(Request $request, $year = null)
    {
        $userId = Auth::id() ?? 1;

        // 🔥 prioridade: GET → rota → atual
        $year = $request->get('year') ?? $year ?? date('Y');

        // 🔥 lista de anos (2024 até atual)
        $currentYear = date('Y');
        $years = range(2024, $currentYear);

        $data = \App\Services\Trading\AnnualTaxService::calculate($userId, $year);

        return view('pages.tax_report_ir', [
            'months' => $data['months'],
            'year' => $year,
            'years' => $years
        ]);
    }
}
