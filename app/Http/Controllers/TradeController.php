<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{

    /**
     * Listagem de operações
     */
    public function index()
    {
        $trades = Trade::latest()->paginate(50);

        return view('trades.index', compact('trades'));
    }


    /**
     * Formulário de edição
     */
    public function edit($id)
    {
        $trade = Trade::findOrFail($id);

        return view('trades.edit', compact('trade'));
    }


    /**
     * Atualizar operação
     */
    public function update(Request $request, $id)
    {
        $trade = Trade::findOrFail($id);

        $request->validate([
            'asset' => 'required|string|max:20',
            'market' => 'required|string|max:20',
            'trade_type' => 'required|string|max:20',
            'side' => 'required|string|max:10',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
        ]);

        $trade->update($request->all());

        return redirect()
            ->route('trades.index')
            ->with('success', 'Operação atualizada com sucesso.');
    }


    /**
     * Excluir operação
     */
    public function destroy($id)
    {
        $trade = Trade::findOrFail($id);

        $trade->delete();

        return redirect()
            ->route('trades.index')
            ->with('success', 'Operação removida.');
    }

}