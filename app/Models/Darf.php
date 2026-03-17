<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Darf extends Model
{

protected $table = 'darfs';

    protected $fillable = [

        'user_id',
        'month',
        'tax_amount',
        'due_date',
        'status'

    ];

}