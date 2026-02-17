<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos para SeoSettings si no existen
        $view = Permission::firstOrCreate(['name' => 'view SeoSettings']);
        $update = Permission::firstOrCreate(['name' => 'update SeoSettings']);

        // Asignar al rol super_admin si existe
        $role = Role::where('name', 'super_admin')->first();

        if ($role) {
            $role->givePermissionTo([$view, $update]);
            $this->command->info('✅ Permisos SEO asignados a super_admin.');
        } else {
            $this->command->warn('⚠️ Rol super_admin no encontrado. No se asignaron permisos.');
        }
    }
}
