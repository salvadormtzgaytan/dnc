<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogRegion extends Model
{
    use HasFactory;

    protected $fillable = ['division_id', 'name'];

    /**
     * Relación: esta región pertenece a una división.
     */
    public function division()
    {
        return $this->belongsTo(CatalogDivision::class, 'division_id');
    }

    /**
     * Relación: una región tiene muchas zonas (territorios).
     */
    public function zones()
    {
        return $this->hasMany(CatalogZone::class, 'region_id');
    }

    /**
     * Relación: acceso directo a tiendas (opcional, útil para reportes).
     */
    public function stores()
    {
        return $this->hasMany(CatalogStore::class, 'region_id');
    }

    public function scopeWithRealExamAverage(Builder $query): Builder
{
    return $query->addSelect([
        'real_exam_avg' => function ($query) {
            $query->selectRaw('AVG(COALESCE(ea.score, 0))')
                ->from('dnc_user_assignments as dua')
                ->join('users as u', 'dua.user_id', '=', 'u.id')
                ->join('user_profiles as up', 'u.id', '=', 'up.user_id')
                ->join('catalog_stores as cs', 'up.store_id', '=', 'cs.id')
                ->join('catalog_zones as cz', 'cs.zone_id', '=', 'cz.id')
                ->join('catalog_regions as cr', 'cz.region_id', '=', 'cr.id')
                ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                ->leftJoin('exam_attempts as ea', function ($join) {
                    $join->on('u.id', '=', 'ea.user_id')
                         ->on('ea.exam_id', '=', 'de.exam_id')
                         ->where('ea.status', '=', 'completed');
                })
                ->whereColumn('cr.id', 'catalog_regions.id');
        }
    ]);
}

}
