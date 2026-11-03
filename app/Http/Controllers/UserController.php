<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Throwable;
use Vinkla\Hashids\Facades\Hashids;

class UserController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $this->authorizeUserManagement($authUser);

        if ($authUser->hasRole('Super-admin')) {
            $users = User::with(['company', 'roles'])
                ->where('id', '!=', $authUser->id)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereRaw('LOWER(name) IN (?, ?)', [
                        'super-admin',
                        'learner',
                    ]);
                })
                ->latest()
                ->get();

            $companies = Company::orderBy('name')->get();

            $roles = Role::whereRaw('LOWER(name) NOT IN (?, ?)', [
                    'super-admin',
                    'learner',
                ])
                ->orderBy('name')
                ->get();
        } else {
            $users = User::with(['company', 'roles'])
                ->where('company_id', $authUser->company_id)
                ->where('id', '!=', $authUser->id)
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereRaw('LOWER(name) IN (?, ?, ?)', [
                        'super-admin',
                        'admin',
                        'learner',
                    ]);
                })
                ->latest()
                ->get();

            $companies = Company::whereKey($authUser->company_id)
                ->orderBy('name')
                ->get();

            $roles = Role::whereRaw('LOWER(name) NOT IN (?, ?, ?)', [
                    'super-admin',
                    'admin',
                    'learner',
                ])
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('users/indexUser', [
            'users' => $users,
            'companies' => $companies,
            'roles' => $roles,
            'authUser' => $this->formatAuthUser($authUser),
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $this->authorizeUserManagement($authUser);

        $rules = $this->validationRules($authUser);
        $rules['email'] = [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email'),
        ];
        $rules['password'] = [
            'required',
            'string',
            'min:8',
        ];

        $validated = $request->validate($rules);
        $photoPath = null;

        try {
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')
                    ->store('profile-photos', 'public');
            }

            DB::transaction(function () use (
                $validated,
                $authUser,
                $photoPath
            ) {
                $user = User::create([
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => filled($validated['middle_name'] ?? null)
                        ? trim($validated['middle_name'])
                        : null,
                    'last_name' => trim($validated['last_name']),
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'place_of_birth' => filled($validated['place_of_birth'] ?? null)
                        ? trim($validated['place_of_birth'])
                        : null,
                    'country' => filled($validated['country'] ?? null)
                        ? trim($validated['country'])
                        : null,
                    'email' => trim($validated['email']),
                    'password' => Hash::make($validated['password']),
                    'company_id' => $this->resolveCompanyId(
                        $authUser,
                        $validated
                    ),
                    'status' => $validated['status'],
                    'profile_photo_path' => $photoPath,
                    'email_verified_at' => null,
                ]);

                $user->syncRoles([$validated['role']]);
            });

            return back()->with('success', __('User added successfully.'));
        } catch (Throwable $exception) {
            $this->deletePublicFile($photoPath);
            report($exception);

            return back()->withErrors([
                'error' => __('An error occurred while adding the user.'),
            ]);
        }
    }

    public function update(Request $request, string $hashid)
    {
        $authUser = auth()->user();
        $this->authorizeUserManagement($authUser);

        $user = User::with('roles')
            ->findOrFail($this->decodeHashid($hashid));

        $this->authorizeUserAccess($authUser, $user);

        $rules = $this->validationRules($authUser);
        $rules['email'] = [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ];
        $rules['password'] = [
            'nullable',
            'string',
            'min:8',
        ];
        $rules['remove_photo'] = [
            'nullable',
            'boolean',
        ];

        $validated = $request->validate($rules);

        $oldPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;
        $deleteOldPhoto = false;

        try {
            if ($request->hasFile('photo')) {
                $newPhotoPath = $request->file('photo')
                    ->store('profile-photos', 'public');
                $deleteOldPhoto = true;
            } elseif ($request->boolean('remove_photo')) {
                $deleteOldPhoto = true;
            }

            DB::transaction(function () use (
                $user,
                $validated,
                $authUser,
                $newPhotoPath,
                $deleteOldPhoto
            ) {
                $user->first_name = trim($validated['first_name']);
                $user->middle_name = filled($validated['middle_name'] ?? null)
                    ? trim($validated['middle_name'])
                    : null;
                $user->last_name = trim($validated['last_name']);
                $user->date_of_birth = $validated['date_of_birth'] ?? null;
                $user->place_of_birth = filled($validated['place_of_birth'] ?? null)
                    ? trim($validated['place_of_birth'])
                    : null;
                $user->country = filled($validated['country'] ?? null)
                    ? trim($validated['country'])
                    : null;
                $user->email = trim($validated['email']);
                $user->company_id = $this->resolveCompanyId(
                    $authUser,
                    $validated
                );
                $user->status = $validated['status'];

                if (filled($validated['password'] ?? null)) {
                    $user->password = Hash::make($validated['password']);
                }

                if ($newPhotoPath) {
                    $user->profile_photo_path = $newPhotoPath;
                } elseif ($deleteOldPhoto) {
                    $user->profile_photo_path = null;
                }

                $user->save();
                $user->syncRoles([$validated['role']]);
            });

            if ($deleteOldPhoto) {
                $this->deletePublicFile($oldPhotoPath);
            }

            return back()->with('success', __('User updated successfully.'));
        } catch (Throwable $exception) {
            $this->deletePublicFile($newPhotoPath);
            report($exception);

            return back()->withErrors([
                'error' => __('An error occurred while updating the user.'),
            ]);
        }
    }

    public function destroy(string $hashid)
    {
        $authUser = auth()->user();
        $this->authorizeUserManagement($authUser);

        try {
            $user = User::with('roles')
                ->findOrFail($this->decodeHashid($hashid));

            $this->authorizeUserAccess($authUser, $user);

            $photoPath = $user->profile_photo_path;

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            $this->deletePublicFile($photoPath);

            return back()->with('success', __('User deleted successfully.'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'error' => __('An error occurred while deleting the user.'),
            ]);
        }
    }

    private function validationRules(User $authUser): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'place_of_birth' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country' => ['nullable', 'string', 'max:255'],
            'company_id' => $authUser->hasRole('Super-admin')
                ? ['required', 'exists:companies,id']
                : ['nullable'],
            'role' => [
                'required',
                'string',
                Rule::in($this->allowedRoleNames($authUser)),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    private function allowedRoleNames(User $authUser): array
    {
        $query = Role::query();

        if ($authUser->hasRole('Super-admin')) {
            $query->whereRaw('LOWER(name) NOT IN (?, ?)', [
                'super-admin',
                'learner',
            ]);
        } else {
            $query->whereRaw('LOWER(name) NOT IN (?, ?, ?)', [
                'super-admin',
                'admin',
                'learner',
            ]);
        }

        return $query->pluck('name')->all();
    }

    private function authorizeUserManagement(User $authUser): void
    {
        abort_unless(
            $authUser->hasRole('Super-admin') ||
            $authUser->hasRole('Admin'),
            403
        );
    }

    private function authorizeUserAccess(User $authUser, User $user): void
    {
        abort_if((int) $authUser->id === (int) $user->id, 403);

        $targetRoleNames = $user->roles
            ->pluck('name')
            ->map(fn ($role) => strtolower($role));

        abort_if($targetRoleNames->contains('super-admin'), 403);
        abort_if($targetRoleNames->contains('learner'), 403);

        if ($authUser->hasRole('Admin')) {
            abort_if(
                (int) $user->company_id !== (int) $authUser->company_id,
                403
            );

            abort_if($targetRoleNames->contains('admin'), 403);
        }
    }

    private function resolveCompanyId(
        User $authUser,
        array $validated
    ): ?int {
        if ($authUser->hasRole('Super-admin')) {
            return (int) $validated['company_id'];
        }

        return $authUser->company_id
            ? (int) $authUser->company_id
            : null;
    }

    private function decodeHashid(string $hashid): int
    {
        $decoded = Hashids::decode($hashid);
        abort_if(empty($decoded), 404);

        return (int) $decoded[0];
    }

    private function deletePublicFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function formatAuthUser(User $authUser): array
    {
        return [
            'id' => $authUser->id,
            'role' => $authUser->hasRole('Super-admin')
                ? 'Super-admin'
                : 'Admin',
            'company_id' => $authUser->company_id,
        ];
    }
}
