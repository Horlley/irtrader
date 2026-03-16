<?php

namespace App\Services\Tax;

use App\Models\MonthlyResult;
use Carbon\Carbon;

class DARFGenerator
{

    public static function generate($userId, $year, $month)
    {

        $result = MonthlyResult::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$result) {
            return null;
        }

        $tax = $result->tax_due;

        /*
        |--------------------------------
        | REGRA RECEITA FEDERAL
        |--------------------------------
        */

        if ($tax < 10) {
            return null;
        }

        /*
        |--------------------------------
        | DATA DE VENCIMENTO
        |--------------------------------
        */

        $dueDate = Carbon::create($year, $month, 1)
            ->addMonth()
            ->endOfMonth();

        /*
        |--------------------------------
        | SALVA DARF
        |--------------------------------
        */

        $result->update([

            'darf_generated' => true,
            'darf_due_date' => $dueDate,
            'darf_value' => $tax

        ]);

        return $result;

    }

}