<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyResult extends Model
{

    protected $fillable = [

        'user_id',
        'year',
        'month',

        'profit_daytrade',
        'profit_swing',
        'profit_futuro',

        'loss_daytrade',
        'loss_swing',
        'loss_futuro',

        'carry_loss_daytrade',
        'carry_loss_swing',
        'carry_loss_futuro',

        'total_sales',

        'tax_daytrade',
        'tax_swing',
        'tax_futuro',
        'tax_due',

        'darf_generated',
        'darf_due_date',
        'darf_value'

    ];

}