<?php

namespace App\Http\Controllers;

use App\Models\TaxConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id() ?? 1;
        $year = (int) $request->get('year', date('Y'));

        $config = TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->first();

        $years = range(2024, max((int) date('Y') + 1, $year));

        return view('pages.settings', [
            'config' => $config,
            'year' => $year,
            'years' => $years,
        ]);
    }

    public function save(Request $request)
    {
        $userId = Auth::id() ?? 1;

        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'initial_loss_daytrade' => 'nullable|string',
            'initial_irrf_daytrade' => 'nullable|string',
        ]);

        TaxConfig::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => (int) $data['year'],
            ],
            [
                'initial_loss_daytrade' => $this->moneyInput($data['initial_loss_daytrade'] ?? 0),
                'initial_irrf_daytrade' => $this->moneyInput($data['initial_irrf_daytrade'] ?? 0),
            ]
        );

        return redirect()
            ->route('settings.index', ['year' => $data['year']])
            ->with('success', 'Configurações salvas com sucesso');
    }

    public function destroy(Request $request)
    {
        $userId = Auth::id() ?? 1;
        $year = (int) $request->get('year', date('Y'));

        TaxConfig::where('user_id', $userId)
            ->where('year', $year)
            ->delete();

        return redirect()
            ->route('settings.index', ['year' => $year])
            ->with('success', 'Configuracao fiscal removida.');
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
}
