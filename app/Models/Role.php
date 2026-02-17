<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends SpatieRole
{
    use HasFactory;

    protected $table = 'roles'; // 👈 Asegurar que use la tabla correcta

    public function scopeDefault($query)
    {
        return $query->firstOrCreate(
            ['name' => 'usuario'],
            ['guard_name' => 'web']
        );
    }
}
