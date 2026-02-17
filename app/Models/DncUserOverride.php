<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DncUserOverride extends Model
{
    protected $fillable = [
        'dnc_id',
        'user_id',
        'custom_start_date',
        'custom_end_date',
        'reason',
    ];

    protected $casts = [
        'custom_start_date' => 'datetime',
        'custom_end_date' => 'datetime',
    ];

    public function dnc()
    {
        return $this->belongsTo(Dnc::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
