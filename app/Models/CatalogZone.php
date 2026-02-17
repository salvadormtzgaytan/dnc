<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogZone extends Model
{
    use HasFactory;

    protected $fillable = ['region_id', 'name'];

    /**
     * Relación: esta zona pertenece a una región.
     */
    public function region()
    {
        return $this->belongsTo(CatalogRegion::class, 'region_id');
    }

    /**
     * Relación: esta zona tiene muchos perfiles de usuario (relación lógica).
     */
    public function profiles()
    {
        return $this->hasMany(UserProfile::class, 'catalog_zone_id');
    }

    /**
     * Relación: esta zona tiene muchas concesionarias.
     */
    public function dealerships()
    {
        return $this->hasMany(CatalogDealership::class, 'zone_id');
    }

    /**
     * Relación: esta zona tiene muchas tiendas (opcional para reportes).
     */
    public function stores()
    {
        return $this->hasMany(CatalogStore::class, 'zone_id');
    }
    /**
     * Scope para agregar el promedio real de calificaciones de exámenes por zona.
     */
public function scopeWithRealExamAverage(Builder $query): Builder
{
    return $query->addSelect([
        'real_exam_avg' => function ($query) {
            $query->selectRaw('AVG(COALESCE(ea.score, 0))')
                ->from('dnc_user_assignments as dua')
                ->join('users as u', 'dua.user_id', '=', 'u.id')
                ->join('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->join('catalog_stores as cs', 'up.store_id', '=', 'cs.id')
                ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                ->leftJoin('exam_attempts as ea', function ($join) {
                    $join->on('u.id', '=', 'ea.user_id')
                         ->on('ea.exam_id', '=', 'de.exam_id')
                         ->where('ea.status', '=', 'completed');
                })
                ->whereColumn('cs.zone_id', 'catalog_zones.id');
        }
    ]);
}

}
