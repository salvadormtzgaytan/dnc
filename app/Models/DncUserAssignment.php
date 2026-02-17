<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DncUserAssignment extends Model
{
    use HasFactory;
    protected $fillable = ['dnc_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dnc()
    {
        return $this->belongsTo(Dnc::class);
    }
}
