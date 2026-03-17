<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDF\PdfReader;
use App\Services\PDF\BrokerDetector;
use App\Services\PDF\XPParser;

use App\Models\Trade;
use App\Models\Import;

use Carbon\Carbon;

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

        // lê texto do PDF
        $text = PdfReader::read($path);

        //dd($text);

        // normaliza texto
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\t/", " ", $text);

        // detecta corretora
        $broker = BrokerDetector::detect($text);

        
        if ($broker !== 'xp') {
            return redirect()->back()->with('error', 'Corretora não suportada');
        }

        // extrai número da nota
        $noteNumber = XPParser::extractNoteNumber($text);

        if (!$noteNumber) {
            return redirect()->back()->with('error', 'Não foi possível identificar o número da nota');
        }

        // verifica duplicidade
        $exists = Import::where('note_number', $noteNumber)->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Esta nota já foi importada');
        }

        // extrai trades
        $trades = XPParser::parse($text);

        if (!$trades || count($trades) === 0) {
            return redirect()->back()->with('error', 'Nenhuma operação encontrada na nota');
        }

        // extrai resumo
        $summary = XPParser::extractSummary($text);

        // tenta pegar DATA PREGÃO
        $noteDate = XPParser::extractTradeDate($text);

        if ($noteDate) {

            $date = Carbon::createFromFormat('d/m/Y', $noteDate)->format('Y-m-d');

        } else {

            // fallback se parser não encontrar a data
            $dates = array_column($trades, 'date');
            sort($dates);

            $date = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d');

        }

        // cria registro da nota
        $import = Import::create([

            'user_id' => 1,
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

            'file_name' => $path
        ]);

        
        // salva trades
        foreach ($trades as $trade) {

            Trade::create([
                'import_id' => $import->id,
                'user_id' => 1,
                'trade_date' => $date, // usa sempre a data da nota
                'broker' => $broker,
                'asset' => $trade['asset'],
                'market' => 'futures',
                'side' => $trade['side'],
                'quantity' => $trade['quantity'],
                'price' => $trade['price'],
                'trade_type' => 'daytrade',
                'source_file' => $path
            ]);

        }

        return redirect()
            ->route('imports.index')
            ->with('success', 'Nota importada com sucesso');

    }

}