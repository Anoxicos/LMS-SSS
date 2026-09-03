<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\MicrosoftOAuthMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Throwable;
use Vinkla\Hashids\Facades\Hashids;

class LearnerController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $this->authorizeLearnerManagement($authUser);

        if ($authUser->hasRole('Super-admin')) {
            $users = User::with(['company', 'roles'])
                ->where('id', '!=', $authUser->id)
                ->whereHas('roles', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['learner']);
                })
                ->latest()
                ->get();

            $companies = Company::orderBy('name')->get();
        } else {
            $users = User::with(['company', 'roles'])
                ->where('company_id', $authUser->company_id)
                ->where('id', '!=', $authUser->id)
                ->whereHas('roles', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['learner']);
                })
                ->latest()
                ->get();

            $companies = Company::whereKey($authUser->company_id)
                ->orderBy('name')
                ->get();
        }

        $roles = Role::whereRaw('LOWER(name) = ?', ['learner'])
            ->orderBy('name')
            ->get();

        return Inertia::render('learner/indexLearner', [
            'users' => $users,
            'companies' => $companies,
            'roles' => $roles,
            'authUser' => $this->formatAuthUser($authUser),
        ]);
    }

    public function show(string $hashid)
    {
        $authUser = auth()->user();
        $this->authorizeLearnerManagement($authUser);

        $learnerQuery = User::with(['company', 'roles'])
            ->whereHas('roles', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['learner']);
            });

        if ($authUser->hasRole('Admin')) {
            $learnerQuery->where('company_id', $authUser->company_id);
        }

        $learner = $learnerQuery->findOrFail($this->decodeHashid($hashid));

        $enrollments = Enrollment::with([
            'course.category',
            'course.chapters' => function ($query) {
                $query->orderBy('sort_order')
                    ->with(['slides' => function ($slideQuery) {
                        $slideQuery->orderBy('sort_order');
                    }]);
            },
            'currentChapter',
            'chapterProgress.chapter',
            'slideProgress.slide',
        ])
            ->where('user_id', $learner->id)
            ->latest()
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;

                $chaptersWithSlides = $course?->chapters
                    ?->filter(function ($chapter) {
                        return $chapter->slides && $chapter->slides->count() > 0;
                    })
                    ->values() ?? collect();

                $totalChapters = $chaptersWithSlides->count();

                $totalSlides = $chaptersWithSlides->sum(function ($chapter) {
                    return $chapter->slides?->count() ?? 0;
                });

                $completedChapters = $enrollment->chapterProgress
                    ->where('is_completed', true)
                    ->filter(function ($progress) use ($chaptersWithSlides) {
                        return $chaptersWithSlides->contains('id', $progress->chapter_id);
                    })
                    ->count();

                $completedSlides = $enrollment->slideProgress
                    ->where('is_completed', true)
                    ->count();

                $viewedSlides = $enrollment->slideProgress
                    ->where('is_viewed', true)
                    ->count();

                $chapters = $chaptersWithSlides
                    ->map(function ($chapter) use ($enrollment) {
                        $chapterProgress = $enrollment->chapterProgress
                            ->firstWhere('chapter_id', $chapter->id);

                        $slides = $chapter->slides
                            ?->map(function ($slide) use ($enrollment) {
                                $slideProgress = $enrollment->slideProgress
                                    ->firstWhere('slide_id', $slide->id);

                                return [
                                    'id' => $slide->id,
                                    'title' => $slide->title,
                                    'lesson_type' => $slide->lesson_type,
                                    'is_preview' => (bool) $slide->is_preview,
                                    'is_mandatory' => (bool) $slide->is_mandatory,
                                    'sort_order' => $slide->sort_order,
                                    'is_viewed' => (bool) ($slideProgress?->is_viewed ?? false),
                                    'is_completed' => (bool) ($slideProgress?->is_completed ?? false),
                                    'completed_at' => $slideProgress?->completed_at,
                                ];
                            })
                            ->values() ?? collect();

                        return [
                            'id' => $chapter->id,
                            'title' => $chapter->title,
                            'description' => $chapter->description,
                            'sort_order' => $chapter->sort_order,
                            'status' => $chapterProgress?->status ?? 'not_started',
                            'is_completed' => (bool) ($chapterProgress?->is_completed ?? false),
                            'progress_percent' => (float) ($chapterProgress?->progress_percent ?? 0),
                            'started_at' => $chapterProgress?->started_at,
                            'slides_count' => $slides->count(),
                            'slides' => $slides,
                        ];
                    })
                    ->values();

                return [
                    'id' => $enrollment->id,
                    'access_type' => $enrollment->access_type,
                    'status' => $enrollment->status,
                    'progress_percent' => (float) $enrollment->progress_percent,
                    'started_at' => $enrollment->started_at,
                    'completed_at' => $enrollment->completed_at,
                    'expired_at' => $enrollment->expired_at,
                    'is_approved' => (bool) $enrollment->is_approved,
                    'attempts_rest' => $enrollment->attempts_rest,
                    'comment' => $enrollment->comment,
                    'current_chapter' => $enrollment->currentChapter ? [
                        'id' => $enrollment->currentChapter->id,
                        'title' => $enrollment->currentChapter->title,
                    ] : null,
                    'course' => $course ? [
                        'id' => $course->id,
                        'hashid' => $course->hashid ?? null,
                        'title' => $course->title,
                        'description' => $course->description,
                        'image' => $course->image,
                        'image_url' => $course->image_url ?? null,
                        'type' => $course->type,
                        'training_type' => $course->training_type,
                        'skill_level' => $course->skill_level,
                        'course_focus' => $course->course_focus,
                        'language' => $course->language,
                        'price' => $course->price,
                        'currency' => $course->currency,
                        'is_published' => (bool) $course->is_published,
                        'content_type' => $course->content_type ?? 'normal',
                        'category' => $course->category ? [
                            'id' => $course->category->id,
                            'name' => $course->category->name,
                        ] : null,
                    ] : null,
                    'stats' => [
                        'total_chapters' => $totalChapters,
                        'completed_chapters' => $completedChapters,
                        'total_slides' => $totalSlides,
                        'completed_slides' => $completedSlides,
                        'viewed_slides' => $viewedSlides,
                    ],
                    'chapters' => $chapters,
                ];
            })
            ->values();

        $categories = $enrollments
            ->pluck('course.category')
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($category) use ($enrollments) {
                return [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'courses_count' => $enrollments
                        ->filter(function ($enrollment) use ($category) {
                            return ($enrollment['course']['category']['id'] ?? null) === $category['id'];
                        })
                        ->count(),
                ];
            });

        return Inertia::render('learner/showLearner', [
            'learner' => $learner,
            'categories' => $categories,
            'enrollments' => $enrollments,
            'authUser' => $this->formatAuthUser($authUser),
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $this->authorizeLearnerManagement($authUser);

        $rules = $this->validationRules();
        $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users,email'];
        $rules['password'] = ['required', 'string', 'min:8'];
        $rules['passport_number'][] = 'unique:users,passport_number';

        if ($authUser->hasRole('Super-admin')) {
            $rules['company_id'] = ['required', 'exists:companies,id'];
        }

        $validated = $request->validate($rules);

        /*
        |--------------------------------------------------------------------------
        | Temporary password used only for the welcome email
        |--------------------------------------------------------------------------
        |
        | The password is stored hashed in the database. The clear value only
        | exists during this HTTP request so it can be included in the welcome
        | email. Never log or store this value in plain text.
        |
        */
        $temporaryPassword = $validated['password'];

        $photoPath = null;
        $passportPath = null;

        try {
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('profile-photos', 'public');
            }

            if ($request->hasFile('passport_file')) {
                $passportPath = $request->file('passport_file')->store('passports', 'public');
            }

            $user = DB::transaction(function () use (
                $validated,
                $authUser,
                $photoPath,
                $passportPath
            ) {
                $user = User::create([
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => filled($validated['middle_name'] ?? null)
                        ? trim($validated['middle_name'])
                        : null,
                    'last_name' => trim($validated['last_name']),
                    'passport_number' => trim($validated['passport_number']),
                    'passport_file' => $passportPath,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'company_id' => $this->resolveCompanyId($authUser, $validated),
                    'status' => $validated['status'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'place_of_birth' => trim($validated['place_of_birth']),
                    'country' => $validated['country'] ?? null,
                    'profile_photo_path' => $photoPath,
                    'email_verified_at' => null,
                ]);

                $user->syncRoles(['learner']);

                return $user;
            });
        } catch (Throwable $exception) {
            $this->deletePublicFile($photoPath);
            $this->deletePublicFile($passportPath);
            report($exception);

            return back()->withErrors([
                'error' => 'An error occurred while adding the learner.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Welcome email
        |--------------------------------------------------------------------------
        |
        | Send the credentials after the transaction has succeeded. An email
        | failure must never roll back or delete the learner account.
        |
        */
        try {
            $user->loadMissing('company');

            app(MicrosoftOAuthMailer::class)->sendLearnerWelcomeCredentials(
                $user,
                $temporaryPassword
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send learner welcome credentials email.',
                [
                    'learner_id' => $user->id,
                    'learner_email' => $user->email,
                    'error' => $exception->getMessage(),
                ]
            );

            return back()
                ->with('success', 'Learner added successfully.')
                ->with(
                    'warning',
                    'The learner account was created, but the welcome email could not be sent.'
                );
        }

        return back()->with(
            'success',
            'Learner added successfully. Login information has been sent by email.'
        );
    }

    public function update(Request $request, string $hashid)
    {
        $authUser = auth()->user();
        $this->authorizeLearnerManagement($authUser);

        $user = User::whereHas('roles', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['learner']);
        })->findOrFail($this->decodeHashid($hashid));

        $this->authorizeLearnerAccess($authUser, $user);

        $rules = $this->validationRules();
        $rules['email'] = [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ];
        $rules['password'] = ['nullable', 'string', 'min:8'];
        $rules['passport_number'][] = Rule::unique('users', 'passport_number')
            ->ignore($user->id);
        $rules['remove_photo'] = ['nullable', 'boolean'];
        $rules['remove_passport_file'] = ['nullable', 'boolean'];

        if ($authUser->hasRole('Super-admin')) {
            $rules['company_id'] = ['required', 'exists:companies,id'];
        }

        $validated = $request->validate($rules);

        /*
        |--------------------------------------------------------------------------
        | Password notification state
        |--------------------------------------------------------------------------
        |
        | The clear password is kept only in memory for this request. It is never
        | saved or logged in plain text. If no password is submitted, no password
        | notification email is sent.
        |
        */
        $passwordWasChanged = filled($validated['password'] ?? null);
        $newTemporaryPassword = $passwordWasChanged
            ? $validated['password']
            : null;

        $oldPhotoPath = $user->profile_photo_path;
        $oldPassportPath = $user->passport_file;
        $newPhotoPath = null;
        $newPassportPath = null;
        $deleteOldPhoto = false;
        $deleteOldPassport = false;

        try {
            if ($request->hasFile('photo')) {
                $newPhotoPath = $request->file('photo')->store('profile-photos', 'public');
                $deleteOldPhoto = true;
            } elseif ($request->boolean('remove_photo')) {
                $deleteOldPhoto = true;
            }

            if ($request->hasFile('passport_file')) {
                $newPassportPath = $request->file('passport_file')->store('passports', 'public');
                $deleteOldPassport = true;
            } elseif ($request->boolean('remove_passport_file')) {
                $deleteOldPassport = true;
            }

            DB::transaction(function () use (
                $user,
                $validated,
                $authUser,
                $newPhotoPath,
                $newPassportPath,
                $deleteOldPhoto,
                $deleteOldPassport
            ) {
                $user->first_name = trim($validated['first_name']);
                $user->middle_name = filled($validated['middle_name'] ?? null)
                    ? trim($validated['middle_name'])
                    : null;
                $user->last_name = trim($validated['last_name']);
                $user->passport_number = trim($validated['passport_number']);
                $user->email = $validated['email'];
                $user->status = $validated['status'];
                $user->date_of_birth = $validated['date_of_birth'];
                $user->place_of_birth = trim($validated['place_of_birth']);
                $user->country = $validated['country'] ?? null;
                $user->company_id = $this->resolveCompanyId($authUser, $validated);

                if (filled($validated['password'] ?? null)) {
                    $user->password = Hash::make($validated['password']);
                }

                if ($newPhotoPath) {
                    $user->profile_photo_path = $newPhotoPath;
                } elseif ($deleteOldPhoto) {
                    $user->profile_photo_path = null;
                }

                if ($newPassportPath) {
                    $user->passport_file = $newPassportPath;
                } elseif ($deleteOldPassport) {
                    $user->passport_file = null;
                }

                $user->save();
                $user->syncRoles(['learner']);
            });

            if ($deleteOldPhoto) {
                $this->deletePublicFile($oldPhotoPath);
            }

            if ($deleteOldPassport) {
                $this->deletePublicFile($oldPassportPath);
            }
        } catch (Throwable $exception) {
            $this->deletePublicFile($newPhotoPath);
            $this->deletePublicFile($newPassportPath);
            report($exception);

            return back()->withErrors([
                'error' => 'An error occurred while updating the learner.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Password changed email
        |--------------------------------------------------------------------------
        |
        | Send this message only when the administrator actually submitted a new
        | password. Email failure never rolls back the learner update.
        |
        */
        if ($passwordWasChanged && $newTemporaryPassword !== null) {
            try {
                $user->refresh();
                $user->loadMissing('company');

                app(MicrosoftOAuthMailer::class)->sendLearnerPasswordUpdated(
                    $user,
                    $newTemporaryPassword
                );
            } catch (Throwable $exception) {
                Log::error(
                    'Unable to send learner password updated email.',
                    [
                        'learner_id' => $user->id,
                        'learner_email' => $user->email,
                        'error' => $exception->getMessage(),
                    ]
                );

                return back()
                    ->with('success', 'Learner updated successfully.')
                    ->with(
                        'warning',
                        'The password was updated, but the learner notification email could not be sent.'
                    );
            }

            return back()->with(
                'success',
                'Learner updated successfully. The new login information has been sent by email.'
            );
        }

        return back()->with('success', 'Learner updated successfully.');
    }

    public function destroy(string $hashid)
    {
        $authUser = auth()->user();
        $this->authorizeLearnerManagement($authUser);

        try {
            $user = User::whereHas('roles', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['learner']);
            })->findOrFail($this->decodeHashid($hashid));

            $this->authorizeLearnerAccess($authUser, $user);

            $photoPath = $user->profile_photo_path;
            $passportPath = $user->passport_file;

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            $this->deletePublicFile($photoPath);
            $this->deletePublicFile($passportPath);

            return back()->with('success', 'Learner deleted successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'error' => 'An error occurred while deleting the learner.',
            ]);
        }
    }

    private function validationRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'passport_number' => ['required', 'string', 'max:100'],
            'passport_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    private function authorizeLearnerManagement(User $authUser): void
    {
        abort_unless(
            $authUser->hasRole('Super-admin') || $authUser->hasRole('Admin'),
            403
        );
    }

    private function authorizeLearnerAccess(User $authUser, User $learner): void
    {
        if (
            $authUser->hasRole('Admin') &&
            (int) $learner->company_id !== (int) $authUser->company_id
        ) {
            abort(403);
        }
    }

    private function resolveCompanyId(User $authUser, array $validated): ?int
    {
        if ($authUser->hasRole('Super-admin')) {
            return (int) $validated['company_id'];
        }

        return $authUser->company_id ? (int) $authUser->company_id : null;
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
            'role' => $authUser->hasRole('Super-admin') ? 'Super-admin' : 'Admin',
            'company_id' => $authUser->company_id,
        ];
    }
}
