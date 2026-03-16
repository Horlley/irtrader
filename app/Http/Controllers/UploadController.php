<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDF\PdfReader;
use App\Services\PDF\BrokerDetector;
use App\Services\PDF\XPParser;
use App\Models\Trade;
use App\Models\BrokerNote;
use Carbon\Carbon;

class UploadController extends Controller
{

    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {

        $request->validate([
            'pdf' => 'required|mimes:pdf'
        ]);

        $file = $request->file('pdf');
        $path = $file->store('pdfs');

        $text = PdfReader::read($path);
        $broker = BrokerDetector::detect($text);

        if ($broker !== 'xp') {
            return redirect()->back()->with('error', 'Corretora não suportada');
        }

        // extrai número da nota
        $noteNumber = XPParser::extractNoteNumber($text);

        if (!$noteNumber) {
            return redirect()->back()->with('error', 'Não foi possível identificar o número da nota');
        }

        // verifica se a nota já foi importada
        $exists = BrokerNote::where('user_id', 1)
            ->where('note_number', $noteNumber)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Esta nota já foi importada');
        }

        // cria registro da nota
        $note = BrokerNote::create([
            'user_id' => 1,
            'note_number' => $noteNumber,
            'broker' => $broker,
            'source_file' => $path
        ]);

        $trades = XPParser::parse($text);

        foreach ($trades as $trade) {

            $date = Carbon::createFromFormat('d/m/Y', $trade['date'])->format('Y-m-d');

            Trade::create([
                'user_id' => 1,
                'note_id' => $note->id,
                'trade_date' => $date,
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

        return redirect()->back()->with('success', 'Trades importados com sucesso');

    }

}