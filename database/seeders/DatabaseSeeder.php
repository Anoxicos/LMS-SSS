<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Pest\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call(PermissionsSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AccreditationSeeder::class);

        $this->callWith(PlanDetailsSeeder::class, [
            'planId' => 1,
        ]);
         $user = User::firstOrCreate(
            [
                'email' => 'tarun@themesbrand.com',
            ],
            [
                'first_name' => "abdellatif",
                'last_name' => "ait touchent",
                'email' =>"tarun@themesbrand.com",
                'email_verified_at' => now(),
                // 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'password' => Hash::make('12345678'),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'remember_token' => Str::random(10),
                'profile_photo_path' => null,
            ]
        );

        $user->assignRole('Super-admin');
    }
}
