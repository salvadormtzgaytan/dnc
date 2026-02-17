<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'url_visited',
        'visited_at',
        'country',
        'city',
        'lat',
        'lon',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

