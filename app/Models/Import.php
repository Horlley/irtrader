<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{

    protected $fillable = [

        'user_id',
        'note_number',
        'broker',
        'trade_date',

        'gross_value',
        'operational_fee',
        'bmf_registration_fee',
        'bmf_fees',

        'irrf_daytrade_proj',
        'irrf_daytrade',

        'total_costs',
        'daytrade_adjustment',
        'account_normal_total',
        'net_total',

        'trades_json',
        'file_name'

    ];

    public function trades()
    {
        return $this->hasMany(\App\Models\Trade::class, 'import_id');
    }
}
