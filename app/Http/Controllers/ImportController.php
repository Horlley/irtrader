<?php

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{

    /**
     * Listar notas de corretagem
     */
    public function index()
    {

        $imports = \App\Models\Import::withCount('trades')
            ->orderBy('trade_date', 'desc')
            ->get();

        return view('imports.index', compact('imports'));
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
