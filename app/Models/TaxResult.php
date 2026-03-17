<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxResult extends Model
{

    protected $fillable = [

        'user_id',
        'month',

        'profit_daytrade',
        'irrf_daytrade',

        'loss_carry_forward',

        'taxable_base',

        'tax_due',

        'darf_due'

    ];

}