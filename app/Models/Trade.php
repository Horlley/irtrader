<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{

    protected $fillable = [
        'user_id',
        'note_number',
        'trade_date',
        'broker',
        'asset',
        'market',
        'side',
        'quantity',
        'price',
        'trade_type',
        'source_file'
    ];


    protected $casts = [
        'trade_date' => 'date',
    ];
}
