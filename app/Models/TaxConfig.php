<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxConfig extends Model
{

    protected $fillable = [

        'user_id',
        'year',
        'initial_loss_daytrade',
        'initial_irrf_daytrade'

    ];

}