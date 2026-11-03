<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;


class RoleController extends Controller
{
    
    public function index(){

        $roles = Role::whereNotIn('name', ['Super-admin', 'candidature'])->get();
        
        return Inertia::render('roles/indexRoles' , [
            "roles" => $roles,
           
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Nettoyer + mettre 1ère lettre en majuscule
        $name = Str::ucfirst(Str::lower(trim($request->name))); 
        // Exemple: "aDMIN" -> "Admin", " super admin " -> "Super admin"

        // Vérifier existence (case-insensitive)
        $exists = Role::whereRaw('LOWER(name) = ?', [Str::lower($name)])->exists();
        if ($exists) {
            return back()->withErrors(['name' => __('This role already exists.')]);
        }

        Role::create(['name' => $name]);

        return back()->with('success', __('Role created successfully.'));
    }


    public function editRole($role){
        $roleEdit = Role::findOrFail($role);
        $permissions = $roleEdit->permissions;
        $permissionsRole = $roleEdit->permissions->pluck('id')->toArray();
        $permissions = Permission::all();

        $editAction = true ;
        return Inertia::render('roles/indexEditRole' , [
            "role" => $roleEdit ,
            "permissions" => $permissions,
            "permissionsRole"=> $permissionsRole,
            "editAction" => $editAction 
        ]);
    }

    public function showRole($role){
        $roleEdit = Role::findOrFail($role);
        $permissions = $roleEdit->permissions;
        $permissionsRole = $roleEdit->permissions->pluck('id')->toArray();
       
        $permissions = Permission::all();
        $editAction = false ;
        return Inertia::render('roles/indexEditRole' , [
            "role" => $roleEdit ,
            "permissions" => $permissions,
            "permissionsRole"=> $permissionsRole,
            "editAction" => $editAction 
        ]);
    }

    public function storeEditRole(Request $request , $role){

        $validatedData =  $request->validate([
            'permissions' => 'array',
        ]);


         $roleEdit = Role::findOrFail($role);
         $roleEdit->permissions()->sync($validatedData['permissions'] ?? []);

    
         return redirect()->back()->with('success', __('Permissions updated successfully.'));

    }
}
