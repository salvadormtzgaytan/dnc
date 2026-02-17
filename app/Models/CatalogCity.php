<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogCity extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'state_id'];

    /**
     * Relación: una ciudad pertenece a un estado.
     */
    public function state()
    {
        return $this->belongsTo(CatalogState::class, 'state_id');
    }
}
