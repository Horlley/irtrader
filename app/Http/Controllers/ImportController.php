<?php

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{

    /**
     * Listar notas de corretagem
     */
    public function index(Request $request)
{
    $query = \App\Models\Import::withCount('trades');

    // 🔥 FILTRO ANO
    if ($request->year) {
        $query->whereYear('trade_date', $request->year);
    }

    // 🔥 FILTRO MÊS
    if ($request->month) {
        $query->whereMonth('trade_date', $request->month);
    }

    // 🔥 FILTRO CORRETORA
    if ($request->broker) {
        $query->where('broker', $request->broker);
    }

    // 🔥 RESULTADO
    $imports = $query
        ->orderBy('trade_date', 'asc')
        ->get();

    // 🔥 DADOS PARA OS FILTROS
    $years = \App\Models\Import::selectRaw('YEAR(trade_date) as year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');

    $brokers = \App\Models\Import::select('broker')
        ->distinct()
        ->orderBy('broker')
        ->pluck('broker');

    return view('imports.index', compact('imports', 'years', 'brokers'));
}


    /**
     * Mostrar operações da nota
     */
    public function show($id)
    {

        $import = Import::with('trades')->findOrFail($id);

        return response()->json($import);
    }


    /**
     * Excluir nota completa
     */
    public function destroy($id)
    {

        $import = \App\Models\Import::findOrFail($id);

        $import->delete();

        return redirect()
            ->route('imports.index')
            ->with('success', 'Nota excluída com sucesso');
    }

    public function trades($id)
    {
        $trades = \App\Models\Trade::where('import_id', $id)->get();
        return response()->json($trades);
    }
}
