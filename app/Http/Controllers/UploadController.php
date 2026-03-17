<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDF\PdfReader;
use App\Services\PDF\BrokerDetector;
use App\Services\PDF\XPParser;

use App\Models\Trade;
use App\Models\Import;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UploadController extends Controller
{

    public function index()
    {
        return view('pages.imports');
    }

    public function upload(Request $request)
    {

        $request->validate([
            'file' => 'required|mimes:pdf'
        ]);

        $file = $request->file('file');
        $path = $file->store('pdfs');

        // 🔥 lê texto do PDF
        $text = PdfReader::read($path);

        // 🔥 normaliza texto
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\t/", " ", $text);

        // 🔥 detecta corretora
        $broker = BrokerDetector::detect($text);

        if ($broker !== 'xp') {
            return redirect()->back()->with('error', 'Corretora não suportada');
        }

        // 🔥 extrai número da nota
        $noteNumber = XPParser::extractNoteNumber($text);

        if (!$noteNumber) {
            return redirect()->back()->with('error', 'Não foi possível identificar o número da nota');
        }

        // 🔥 evita duplicidade
        if (Import::where('note_number', $noteNumber)->exists()) {
            return redirect()->back()->with('error', 'Esta nota já foi importada');
        }

        // 🔥 extrai trades
        $trades = XPParser::parse($text);

        if (!$trades || count($trades) === 0) {
            return redirect()->back()->with('error', 'Nenhuma operação encontrada na nota');
        }

        // 🔥 extrai resumo
        $summary = XPParser::extractSummary($text);

        // 🔥 DATA DO PREGÃO
        $noteDate = XPParser::extractTradeDate($text);

        if ($noteDate) {
            $date = Carbon::createFromFormat('d/m/Y', $noteDate)->format('Y-m-d');
        } else {
            $dates = array_column($trades, 'date');
            sort($dates);
            $date = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
        }

        DB::beginTransaction();

        try {

            // =========================
            // 🔥 SALVA IMPORT (AGORA CORRETO)
            // =========================
            $import = Import::create([
                'user_id' => Auth::id() ?? 1,
                'note_number' => $noteNumber,
                'broker' => $broker,
                'trade_date' => $date,

                'gross_value' => $summary['gross_value'] ?? 0,
                'operational_fee' => $summary['operational_fee'] ?? 0,
                'bmf_registration_fee' => $summary['bmf_registration_fee'] ?? 0,
                'bmf_fees' => $summary['bmf_fees'] ?? 0,

                'irrf_daytrade_proj' => $summary['irrf_daytrade_proj'] ?? 0,

                'total_costs' => $summary['total_costs'] ?? 0,
                'daytrade_adjustment' => $summary['daytrade_adjustment'] ?? 0,
                'account_normal_total' => $summary['account_normal_total'] ?? 0,
                'net_total' => $summary['net_total'] ?? 0,

                // 🔥 ESSENCIAL PRA FUNCIONAR O RELATÓRIO
                'trades_json' => json_encode($trades),

                'file_name' => $path
            ]);

            // =========================
            // 🔥 SALVA TRADES (NORMALIZADO)
            // =========================
            foreach ($trades as $trade) {

                $sideRaw = strtoupper(trim($trade['side'] ?? ''));

                $side = null;

                if (in_array($sideRaw, ['C', 'BUY'], true)) {
                    $side = 'buy';
                } elseif (in_array($sideRaw, ['V', 'SELL'], true)) {
                    $side = 'sell';
                }

                if (!$side) {
                    continue;
                }

                Trade::create([
                    'import_id' => $import->id,
                    'user_id' => Auth::id() ?? 1,
                    'trade_date' => $date,
                    'broker' => $broker,
                    'asset' => $trade['asset'] ?? null,
                    'market' => str_contains($trade['asset'] ?? '', 'WDO') ? 'dolar' : 'indice',
                    'side' => $side,
                    'quantity' => (int) ($trade['quantity'] ?? 0),
                    'price' => (float) ($trade['price'] ?? 0),
                    'trade_type' => 'daytrade',
                    'source_file' => $path
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()->with('error', 'Erro ao importar: ' . $e->getMessage());
        }

        return redirect()
            ->route('imports.index')
            ->with('success', 'Nota importada com sucesso');
    }
}