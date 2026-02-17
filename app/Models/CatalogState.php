<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogState extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Relación: un estado tiene muchas ciudades.
     */
    public function cities()
    {
        return $this->hasMany(CatalogCity::class, 'state_id');
    }
}
