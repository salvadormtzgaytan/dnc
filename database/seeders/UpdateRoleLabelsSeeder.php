<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UpdateRoleLabelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Concesionario',
            4 => 'Participante',
        ];

        foreach ($roles as $id => $label) {
            Role::where('id', $id)->update([
                'label' => $label,
            ]);
        }

        $this->command->info('Labels de roles actualizados correctamente.');
    }
}
