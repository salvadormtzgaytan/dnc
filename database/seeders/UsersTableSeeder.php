<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $rows = [];

        // Usuario super_admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin123!'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        $rows[] = [
            'Nombre' => $admin->name,
            'Rol' => 'super_admin',
            'Email' => $admin->email,
            'Password' => 'admin123!',
        ];

        // Crea un usuario por cada rol registrado (excepto super_admin)
        $roles = Role::where('name', '!=', 'super_admin')->get();

        foreach ($roles as $role) {
            $emailPrefix = $this->sanitizeForEmail($role->name);
            $email = "{$emailPrefix}@example.com";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $role->name,
                    'password' => Hash::make('password123!'),
                    'is_active' => true,
                ]
            );

            $user->assignRole($role->name);

            $rows[] = [
                'Nombre' => $user->name,
                'Rol' => $role->name,
                'Email' => $user->email,
                'Password' => 'password123!',
            ];
        }

        // Mostrar tabla en consola
        $this->command->info('Usuarios creados:');
        $this->command->table(
            ['Nombre', 'Rol', 'Email', 'Password'],
            $rows
        );
    }

    protected function sanitizeForEmail(string $name): string
    {
        // Tabla de reemplazo manual para acentos comunes
        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'Ñ' => 'n',
        ];

        $clean = strtr($name, $replacements);             // Reemplazar manualmente tildes y ñ
        $clean = strtolower($clean);                      // Minúsculas
        $clean = preg_replace('/[^a-z0-9]+/', '_', $clean); // Sustituye todo lo que no sea letra o número por "_"
        return trim($clean, '_');                         // Elimina guiones bajos al principio o fin
    }
}
