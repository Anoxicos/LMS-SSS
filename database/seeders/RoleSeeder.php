<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Helpers
        $crud = fn(string $t) => ["read $t","create $t","update $t","delete $t"];
        $r    = fn(string $t) => ["read $t"];
        $crud_app = fn(string $t) => ["read $t","create $t","update $t","delete $t" , "approve $t"];

        // Roles => Permissions (selon ton tableau)
        $roles = [

            // Super-admin => CRUD sur tout (déjà fait aussi dans PermissionsSeeder, mais OK si tu veux ici)
            'Super-admin' => array_merge(
                $crud('user'),
                $crud('learner'),
                $crud('company'),
                $crud('category'),
                $crud('course'),
                $crud('banks'),
                $crud('question'),
                $crud_app('company request'),
            ),
                // Admin => CRUD* sur tout => on donne CRUD (le * se gère via Policy si besoin)
            'Admin' => array_merge(
                $crud('learner'),
                $crud('company request'),
            ),

            'Trainer' => array_merge(
            ),

            'Learner' => array_merge(
            ),


        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }
    }
}
