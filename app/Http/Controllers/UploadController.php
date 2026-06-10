<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDF\PdfReader;
use App\Services\PDF\BrokerDetector;
use App\Services\PDF\BrokerParserFactory;

use App\Models\Trade;
use App\Models\Import;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UploadController extends Controller
{

    public function index()
    {
        $query = Import::withCount('trades');

        if (request('year')) {
            $query->whereYear('trade_date', request('year'));
        }

        if (request('month')) {
            $query->whereMonth('trade_date', request('month'));
        }

        if (request('broker')) {
            $query->where('broker', request('broker'));
        }

        $imports = $query
            ->orderBy('trade_date', 'asc')
            ->get();

        $years = Import::selectRaw('YEAR(trade_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $brokers = Import::select('broker')
            ->distinct()
            ->orderBy('broker')
            ->pluck('broker');

        return view('pages.imports', compact('imports', 'years', 'brokers'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf'
        ]);

        // =========================
        // 🔥 GARANTE PASTA
        // =========================
        $uploadPath = public_path('uploads');

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        // =========================
        // 🔥 SALVA ARQUIVO
        // =========================
        $file = $request->file('file');

        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $file->move($uploadPath, $filename);

        $path = 'uploads/' . $filename;
        $fullPath = public_path($path);

        // =========================
        // 🔥 LÊ PDF
        // =========================
        $text = PdfReader::read($fullPath);

        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\t/", " ", $text);

        $broker = BrokerDetector::detect($text);

        $parser = BrokerParserFactory::make($broker);

        if (!$parser) {
            return $this->response($request, false, 'Corretora não suportada');
        }

        $noteNumber = $parser::extractNoteNumber($text);

        if (!$noteNumber) {
            return $this->response($request, false, 'Não foi possível identificar o número da nota');
        }

        if (Import::where('broker', $broker)->where('note_number', $noteNumber)->exists()) {
            return $this->response($request, false, 'Esta nota já foi importada');
        }

        $trades = $parser::parse($text);

        if (!$trades || count($trades) === 0) {
            return $this->response($request, false, 'Nenhuma operação encontrada na nota');
        }

        $summary = $parser::extractSummary($text);

        $noteDate = $parser::extractTradeDate($text);

        if ($noteDate) {
            $date = Carbon::createFromFormat('d/m/Y', $noteDate)->format('Y-m-d');
        } else {
            $dates = array_column($trades, 'date');
            sort($dates);
            $date = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');
        }

        DB::beginTransaction();

        try {

            $import = Import::create($this->filterColumns('imports', [
                'user_id' => Auth::id() ?? 1,
                'note_number' => $noteNumber,
                'broker' => $broker,
                'trade_date' => $date,

                'total_trades' => count($trades),
                'gross_value' => $summary['gross_value'] ?? 0,
                'operational_fee' => $summary['operational_fee'] ?? 0,
                'bmf_registration_fee' => $summary['bmf_registration_fee'] ?? 0,
                'bmf_fees' => $summary['bmf_fees'] ?? 0,

                'irrf' => $summary['irrf_daytrade_proj'] ?? 0,
                'irrf_daytrade_proj' => $summary['irrf_daytrade_proj'] ?? 0,

                'total_costs' => $summary['total_costs'] ?? 0,
                'daytrade_adjustment' => $summary['daytrade_adjustment'] ?? 0,
                'account_normal_total' => $summary['account_normal_total'] ?? 0,
                'net_result' => $summary['net_total'] ?? 0,
                'net_total' => $summary['net_total'] ?? 0,

                'trades_json' => json_encode($trades),
                'file_name' => $path
            ]));

            foreach ($trades as $trade) {

                $sideRaw = strtoupper(trim($trade['side'] ?? ''));

                $side = null;

                if (in_array($sideRaw, ['C', 'BUY'], true)) {
                    $side = 'buy';
                } elseif (in_array($sideRaw, ['V', 'SELL'], true)) {
                    $side = 'sell';
                }

                if (!$side) continue;

                Trade::create($this->filterColumns('trades', [
                    'import_id' => $import->id,
                    'user_id' => Auth::id() ?? 1,
                    'note_number' => $noteNumber,
                    'trade_date' => $date,
                    'broker' => $broker,
                    'asset' => $trade['asset'] ?? null,
                    'market' => $this->classifyMarket($trade['asset'] ?? ''),
                    'side' => $side,
                    'quantity' => (int) ($trade['quantity'] ?? 0),
                    'price' => (float) ($trade['price'] ?? 0),
                    'result' => (float) ($trade['result'] ?? 0),
                    'trade_type' => $trade['trade_type'] ?? 'daytrade',
                    'source_file' => $path
                ]));
            }

            DB::commit();

            return $this->response($request, true, 'Nota importada com sucesso');

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->response($request, false, 'Erro ao importar: ' . $e->getMessage());
        }
    }

    // =========================
    // 🔥 DELETE PROFISSIONAL (CORRIGIDO)
    // =========================
    public function destroy($id)
    {
        $import = Import::findOrFail($id);

        DB::beginTransaction();

        try {

            // 🔥 caminho completo
            $fullPath = public_path($import->file_name);

            // 🔥 remove arquivo com segurança
            if ($import->file_name && File::exists($fullPath)) {
                File::delete($fullPath);
            }

            // 🔥 remove trades
            Trade::where('import_id', $import->id)->delete();

            // 🔥 remove import
            $import->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Importação excluída com sucesso');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()->with('error', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

    // =========================
    // 🔥 RESPONSE PADRÃO
    // =========================
    private function response($request, $success, $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message
            ]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    private function classifyMarket($asset)
    {
        $asset = strtoupper(trim($asset));

        if (strpos($asset, 'WDO') !== false || strpos($asset, 'DOL') !== false) {
            return 'dolar';
        }

        if (strpos($asset, 'WIN') !== false || strpos($asset, 'IND') !== false) {
            return 'indice';
        }

        if (strpos($asset, 'BIT') !== false) {
            return 'bitcoin';
        }

        return 'outros';
    }

    private function filterColumns($table, array $data)
    {
        return array_filter($data, function ($key) use ($table) {
            return Schema::hasColumn($table, $key);
        }, ARRAY_FILTER_USE_KEY);
    }
}
