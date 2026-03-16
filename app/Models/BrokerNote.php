<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Trade; // ← ADICIONE ESTA LINHA

class BrokerNote extends Model
{

    protected $fillable = [
        'user_id',
        'note_number',
        'broker',
        'source_file'
    ];

    public function trades()
    {
        return $this->hasMany(Trade::class, 'note_id');
    }

}