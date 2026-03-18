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
use Illuminate\Support\Facades\File;

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

        if ($broker !== 'xp') {
            return $this->response($request, false, 'Corretora não suportada');
        }

        $noteNumber = XPParser::extractNoteNumber($text);

        if (!$noteNumber) {
            return $this->response($request, false, 'Não foi possível identificar o número da nota');
        }

        if (Import::where('note_number', $noteNumber)->exists()) {
            return $this->response($request, false, 'Esta nota já foi importada');
        }

        $trades = XPParser::parse($text);

        if (!$trades || count($trades) === 0) {
            return $this->response($request, false, 'Nenhuma operação encontrada na nota');
        }

        $summary = XPParser::extractSummary($text);

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

                'trades_json' => json_encode($trades),
                'file_name' => $path
            ]);

            foreach ($trades as $trade) {

                $sideRaw = strtoupper(trim($trade['side'] ?? ''));

                $side = null;

                if (in_array($sideRaw, ['C', 'BUY'], true)) {
                    $side = 'buy';
                } elseif (in_array($sideRaw, ['V', 'SELL'], true)) {
                    $side = 'sell';
                }

                if (!$side) continue;

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
}