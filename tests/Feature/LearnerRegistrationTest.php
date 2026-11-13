<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a learner is signed in after registration', function () {
    Storage::fake('local');

    $response = $this->post(route('learner.register.store'), [
        'first_name' => 'Amira',
        'last_name' => 'Hassan',
        'email' => 'amira@example.com',
        'country' => 'Morocco',
        'date_of_birth' => '1990-01-01',
        'place_of_birth' => 'Casablanca',
        'passport_number' => 'TEST1234',
        'passport_file' => UploadedFile::fake()->create('passport.pdf', 10, 'application/pdf'),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ]);

    $response->assertRedirect(route('courses'));
    $user = User::where('email', 'amira@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    Storage::disk('local')->assertExists($user->passport_file);
});
