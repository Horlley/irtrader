<?php

namespace App\Services\Darf;

use App\Models\TaxResult;
use App\Models\Darf;
use Carbon\Carbon;

class DarfGenerator
{

    public static function generate($userId)
    {

        $results = TaxResult::where('user_id', $userId)->get();

        foreach ($results as $r) {

            if ($r->darf_due > 10) {

                $dueDate = Carbon::parse($r->month . '-01')
                    ->addMonth()
                    ->endOfMonth()
                    ->format('Y-m-d');

                Darf::updateOrCreate(

                    [
                        'user_id' => $userId,
                        'month' => $r->month
                    ],

                    [
                        'tax_amount' => $r->darf_due,
                        'due_date' => $dueDate
                    ]

                );
            }
        }
    }
}
