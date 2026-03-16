<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{

    protected $fillable = [

        'import_id',
        'user_id',
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

        'price' => 'decimal:2',
        'fees' => 'decimal:2',
        'irrf' => 'decimal:2',
        'result' => 'decimal:2',

    ];


    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}
