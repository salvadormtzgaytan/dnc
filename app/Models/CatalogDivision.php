<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogDivision extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Relación: una división tiene muchas regiones.
     */
    public function regions()
    {
        return $this->hasMany(CatalogRegion::class, 'division_id');
    }

    /**
     * Relación: una división puede tener muchas tiendas (acceso indirecto, útil en reportes).
     */
    public function stores()
    {
        return $this->hasMany(CatalogStore::class, 'division_id');
    }

    // App\Models\CatalogDivision.php

    // Retorna una colección de IDs de usuarios que pertenecen a esta división
    public function getUserIds(): \Illuminate\Support\Collection
    {
        // Filtra usuarios cuya relación en cascada (perfil → tienda → zona → región → división) coincida con esta división
        return \App\Models\User::whereHas(
            'profile.store.zone.region.division',
            fn($q) =>
            $q->where('id', $this->id)
        )->pluck('id'); // Obtiene solo los IDs de los usuarios encontrados
    }

    // Calcula el promedio del puntaje más alto de exámenes completados por usuario en esta división
    public function averageExamScore(): ?float
    {
        $userIds = $this->getUserIds(); // Obtiene los IDs de usuarios en la división
        if ($userIds->isEmpty()) return null; // Retorna null si no hay usuarios

        // Obtiene todos los intentos de examen completados con puntaje válido por los usuarios
        $scores = \App\Models\ExamAttempt::whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->get()
            ->groupBy('user_id') // Agrupa los intentos por usuario
            ->map(fn($attempts) => $attempts->max('score')); // Obtiene el puntaje más alto por usuario

        // Retorna el promedio de los puntajes más altos, o null si no hay datos
        return $scores->count() ? $scores->avg() : null;
    }

    // Cuenta cuántos intentos de examen completados hay en total en esta división
    public function completedExamCount(): int
    {
        $userIds = $this->getUserIds(); // Obtiene los IDs de usuarios en la división
        if ($userIds->isEmpty()) return 0; // Retorna 0 si no hay usuarios

        // Cuenta los intentos de examen con estado 'completed' por los usuarios de la división
        return \App\Models\ExamAttempt::whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->count();
    }

    // Cuenta cuántos usuarios pertenecen a esta división
    public function participantsCount(): int
    {
        // Filtra usuarios cuya relación en cascada (perfil → tienda → zona → región → división) coincida con esta división
        return \App\Models\User::whereHas(
            'profile.store.zone.region.division',
            fn($q) =>
            $q->where('id', $this->id)
        )->count(); // Retorna la cantidad total de usuarios encontrados
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
                ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                ->leftJoin('exam_attempts as ea', function ($join) {
                    $join->on('u.id', '=', 'ea.user_id')
                         ->on('ea.exam_id', '=', 'de.exam_id')
                         ->where('ea.status', '=', 'completed');
                })
                ->whereColumn('cs.division_id', 'catalog_divisions.id');
        }
    ]);
}

}
