<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'external_store_id',
        'external_account_number',
        'business_name',
        'address',
        'division_id',
        'region_id',
        'zone_id',
        'state_id',
        'city_id',
        'dealership_id',
    ];

    /**
     * Relación: la tienda tiene muchos perfiles de usuario asignados.
     */
    public function profiles()
    {
        return $this->hasMany(UserProfile::class, 'store_id');
    }

    /**
     * Relación: esta tienda pertenece a una división.
     */
    public function division()
    {
        return $this->belongsTo(CatalogDivision::class, 'division_id');
    }

    /**
     * Relación: esta tienda pertenece a una región.
     */
    public function region()
    {
        return $this->belongsTo(CatalogRegion::class, 'region_id');
    }

    /**
     * Relación: esta tienda pertenece a una zona.
     */
    public function zone()
    {
        return $this->belongsTo(CatalogZone::class, 'zone_id');
    }

    /**
     * Relación: esta tienda pertenece a un estado.
     */
    public function state()
    {
        return $this->belongsTo(CatalogState::class, 'state_id');
    }

    /**
     * Relación: esta tienda pertenece a una ciudad.
     */
    public function city()
    {
        return $this->belongsTo(CatalogCity::class, 'city_id');
    }

    /**
     * Relación: esta tienda pertenece a una concesionaria.
     */
    public function dealership()
    {
        return $this->belongsTo(CatalogDealership::class, 'dealership_id');
    }

    public function scopeWithRealExamAverage(Builder $query): Builder
{
    return $query->addSelect([
        'real_exam_avg' => function ($query) {
            $query->selectRaw('AVG(COALESCE(ea.score, 0))')
                ->from('dnc_user_assignments as dua')
                ->join('users as u', 'dua.user_id', '=', 'u.id')
                ->join('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->whereColumn('up.store_id', 'catalog_stores.id')
                ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                ->leftJoin('exam_attempts as ea', function ($join) {
                    $join->on('u.id', '=', 'ea.user_id')
                         ->on('ea.exam_id', '=', 'de.exam_id')
                         ->where('ea.status', '=', 'completed');
                });
        }
    ]);
}

}
