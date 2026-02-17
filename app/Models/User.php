<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

//class User extends Authenticatable implements MustVerifyEmail
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll() // loguea todos los atributos
            ->logOnlyDirty() // solo si cambian
            ->logExcept(['password'])
            ->useLogName('user') // nombre del log para filtrarlo
            ->dontSubmitEmptyLogs();
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'rfc',
        'password',
        'is_active',
        'profile_photo',
        'parent_id',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'last_login_at'     => 'datetime',
    ];

    /**
     * The function `isActive` in PHP returns the value of the `is_active` property.
     *
     * @return `isActive()` function is returning the value of the property `->is_active`.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * The function `updatePassword` updates the user's password and logs them out from other devices.
     *
     * param newPassword The `` parameter in the `updatePassword` function represents the new
     * password that will be set for the user. This password is hashed using Laravel's `Hash::make` method
     * before being updated in the database. Additionally, the `Auth::logoutOtherDevices` method is called
     * to log
     */
    public function updatePassword($newPassword)
    {
        $this->update(['password' => Hash::make($newPassword)]);

        Auth::logoutOtherDevices($newPassword);
    }

    /**
     * The `groups` function defines a many-to-many relationship between the current model and the
     * `UserGroup` model using the `user_group_user` pivot table.
     *
     * @return `groups()` function is returning a many-to-many relationship between the current
     * model (assuming it's a User model) and the UserGroup model. The relationship is defined by the
     * `user_group_user` pivot table.
     */
    public function groups()
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_user');
    }

    /**
     * Relaciones jerárquicas
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'parent_id'); // Gerente a cargo
    }

    /* The `subordinates()` function in the `User` model is defining a relationship where a user can have
    multiple subordinates. It uses the `hasMany` relationship method provided by Eloquent to establish a
    one-to-many relationship between the current `User` model and the `User` model itself. */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'parent_id'); // Usuarios a cargo
    }

    /**
     * The profile function defines a one-to-one relationship between the current model and the UserProfile
     * model in PHP.
     *
     * return  snippet is defining a method named `profile` in a PHP class. This method is using
     * Eloquent ORM (assuming it's a Laravel application) to define a one-to-one relationship between the
     * current class and the `UserProfile` model. The `hasOne` method indicates that each instance of the
     * current class has one associated `UserProfile` record.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * The function `dncs` establishes a many-to-many relationship between the current model and the `Dnc`
     * model using the `dnc_user_assignments` table.
     *
     * return A many-to-many relationship is being returned between the current model and the `Dnc` model
     * using the `belongsToMany` method. The relationship is defined by the `dnc_user_assignments` pivot
     * table. The `withTimestamps` method is used to automatically update the timestamps of the pivot
     * table.
     */
    public function dncs()
    {
        return $this->belongsToMany(Dnc::class, 'dnc_user_assignments')
            ->withTimestamps();
    }

    /**
     * The `booted` function adds a global scope to exclude deleted records when the application is not
     * running in the console and the request is not for the login or admin login pages.
     *
     * return If the application is running in console or if the request is for 'login' or 'admin/login',
     * the function will return without applying any additional conditions to the query builder. Otherwise,
     * if none of these conditions are met, the function will add a global scope to the query builder to
     * only include records where the 'deleted_at' column is null.
     */
    protected static function booted()
    {
        parent::boot();
        static::addGlobalScope('notDeletedOnAuth', function ($builder) {
            if (app()->runningInConsole()) {
                return;
            }

            if (request()->is('login') || request()->is('admin/login')) {
                $builder->whereNull('deleted_at');
            }
        });
    }
    /**
     * @inheritDoc
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
 * Mutador para asegurar que el RFC siempre se guarde en mayúsculas
 */
    public function setRfcAttribute($value)
    {
        $this->attributes['rfc'] = $value ? strtoupper($value) : null;
    }

    public function dncAssignments()
    {
        return $this->hasMany(DncUserAssignment::class);
    }


public static function withDncAverageReal(?int $dncId = null)
{
    return self::query()
        ->select('users.*')
        ->addSelect([
            'dnc_avg_real' => function ($query) use ($dncId) {
                $bestScoresSub = DB::table('exam_attempts as ea')
                    ->selectRaw('MAX(ea.score) as score')
                    ->whereColumn('ea.user_id', 'users.id')
                    ->where('ea.status', 'completed')
                    ->whereNotNull('ea.score')
                    ->when($dncId, function ($q) use ($dncId) {
                        $q->join('dnc_exam as de', 'ea.exam_id', '=', 'de.exam_id')
                          ->where('de.dnc_id', $dncId);
                    })
                    ->groupBy('ea.exam_id');

                $query->selectRaw('
                COALESCE((
                    SELECT AVG(score) FROM (' . $bestScoresSub->toSql() . ') AS user_exam_max
                ), 0)
            ')
                    ->mergeBindings($bestScoresSub);
            },
        ])
        ->when(
            $dncId,
            fn ($q) => $q->whereHas('dncAssignments', fn ($q2) => $q2->where('dnc_id', $dncId))
        );
}


}
