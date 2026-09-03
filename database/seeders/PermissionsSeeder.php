<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'read user',
            'create user',
            'update user',
            'delete user',
            'read learner',
            'create learner',
            'update learner',
            'delete learner',
            'read company',
            'create company',
            'update company',
            'delete company',
            'read category',
            'create category',
            'update category',
            'delete category',
            'read course',
            'create course',
            'update course',
            'delete course',
            'read company request',
            'create company request',
            'update company request',
            'delete company request',
            'approve company request',
            'read banks',
            'create banks',
            'update banks',
            'delete banks',
            'read question',
            'create question',
            'update question',
            'delete question',
            
        ];
       
        foreach ($permissions as $permission) {
            // Créer la permission si elle n'existe pas déjà
            $perm = Permission::firstOrCreate(['name' => $permission]);
           
        }
    }
}
