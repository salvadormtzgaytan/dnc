<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogDealership extends Model
{
    use HasFactory;

    protected $fillable = ['zone_id', 'name'];

    /**
     * Relación: esta concesionaria pertenece a una zona.
     */
    public function zone()
    {
        return $this->belongsTo(CatalogZone::class, 'zone_id');
    }

    /**
     * Relación: esta concesionaria tiene muchos perfiles.
     */
    public function profiles()
    {
        return $this->hasMany(UserProfile::class, 'dealership_id');
    }

    /**
     * Relación: esta concesionaria tiene muchas tiendas.
     */
    public function stores()
    {
        return $this->hasMany(CatalogStore::class, 'dealership_id');
    }

    public function scopeWithRealExamAverage(Builder $query): Builder
{
    return $query->addSelect([
        'real_exam_avg' => function ($query) {
            $query->selectRaw('AVG(COALESCE(ea.score, 0))')
                ->from('dnc_user_assignments as dua')
                ->join('users as u', 'dua.user_id', '=', 'u.id')
                ->join('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->whereColumn('up.dealership_id', 'catalog_dealerships.id')
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
