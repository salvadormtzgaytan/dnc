<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'position_id',
        'store_id',
        'zone_id',
        'dealership_id',
    ];

    /**
     * Relación: este perfil pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: puesto del usuario.
     */
    public function position()
    {
        return $this->belongsTo(CatalogPosition::class, 'position_id');
    }

    /**
     * Relación: tienda donde trabaja el usuario.
     */
    public function store()
    {
        return $this->belongsTo(CatalogStore::class, 'store_id');
    }

    /**
     * Relación: zona jerárquica (otro usuario como jefe).
     */
    public function zoneManager()
    {
        return $this->belongsTo(User::class, 'zone_id');
    }

    /**
     * Relación: concesionaria asignada.
     */
    public function dealership()
    {
        return $this->belongsTo(CatalogDealership::class, 'dealership_id');
    }
}
