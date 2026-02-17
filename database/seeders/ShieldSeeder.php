<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_activity::log","view_any_activity::log","create_activity::log","update_activity::log","restore_activity::log","restore_any_activity::log","replicate_activity::log","reorder_activity::log","delete_activity::log","delete_any_activity::log","force_delete_activity::log","force_delete_any_activity::log","view_catalog::dealership","view_any_catalog::dealership","create_catalog::dealership","update_catalog::dealership","restore_catalog::dealership","restore_any_catalog::dealership","replicate_catalog::dealership","reorder_catalog::dealership","delete_catalog::dealership","delete_any_catalog::dealership","force_delete_catalog::dealership","force_delete_any_catalog::dealership","view_catalog::division","view_any_catalog::division","create_catalog::division","update_catalog::division","restore_catalog::division","restore_any_catalog::division","replicate_catalog::division","reorder_catalog::division","delete_catalog::division","delete_any_catalog::division","force_delete_catalog::division","force_delete_any_catalog::division","view_catalog::position","view_any_catalog::position","create_catalog::position","update_catalog::position","restore_catalog::position","restore_any_catalog::position","replicate_catalog::position","reorder_catalog::position","delete_catalog::position","delete_any_catalog::position","force_delete_catalog::position","force_delete_any_catalog::position","view_catalog::region","view_any_catalog::region","create_catalog::region","update_catalog::region","restore_catalog::region","restore_any_catalog::region","replicate_catalog::region","reorder_catalog::region","delete_catalog::region","delete_any_catalog::region","force_delete_catalog::region","force_delete_any_catalog::region","view_catalog::store","view_any_catalog::store","create_catalog::store","update_catalog::store","restore_catalog::store","restore_any_catalog::store","replicate_catalog::store","reorder_catalog::store","delete_catalog::store","delete_any_catalog::store","force_delete_catalog::store","force_delete_any_catalog::store","view_catalog::zone","view_any_catalog::zone","create_catalog::zone","update_catalog::zone","restore_catalog::zone","restore_any_catalog::zone","replicate_catalog::zone","reorder_catalog::zone","delete_catalog::zone","delete_any_catalog::zone","force_delete_catalog::zone","force_delete_any_catalog::zone","view_dnc","view_any_dnc","create_dnc","update_dnc","restore_dnc","restore_any_dnc","replicate_dnc","reorder_dnc","delete_dnc","delete_any_dnc","force_delete_dnc","force_delete_any_dnc","view_exam","view_any_exam","create_exam","update_exam","restore_exam","restore_any_exam","replicate_exam","reorder_exam","delete_exam","delete_any_exam","force_delete_exam","force_delete_any_exam","view_question","view_any_question","create_question","update_question","restore_question","restore_any_question","replicate_question","reorder_question","delete_question","delete_any_question","force_delete_question","force_delete_any_question","view_question::bank","view_any_question::bank","create_question::bank","update_question::bank","restore_question::bank","restore_any_question::bank","replicate_question::bank","reorder_question::bank","delete_question::bank","delete_any_question::bank","force_delete_question::bank","force_delete_any_question::bank","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_user::group","view_any_user::group","create_user::group","update_user::group","restore_user::group","restore_any_user::group","replicate_user::group","reorder_user::group","delete_user::group","delete_any_user::group","force_delete_user::group","force_delete_any_user::group","page_ManageSeo"]},{"name":"administrador","guard_name":"web","permissions":[]},{"name":"dealership","guard_name":"web","permissions":[]},{"name":"participante","guard_name":"web","permissions":[]}]';
        $directPermissions = '[{"name":"super_admin","guard_name":"web"},{"name":"view SeoSettings","guard_name":"web"},{"name":"update SeoSettings","guard_name":"web"}]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
