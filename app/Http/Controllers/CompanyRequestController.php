<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\Enrollment;
use App\Models\CompanyRequest;
use App\Models\SlideProgress;
use App\Models\ChapterProgress;
use App\Models\Company;
use App\Models\Accreditation;
use App\Services\MicrosoftOAuthMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;
use Vinkla\Hashids\Facades\Hashids;

class CompanyRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = CompanyRequest::with([
            'company',
            'course',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ])->latest();

        if (!$user->can('approve company request')) {
            $query->where('company_id', $user->company_id);
        }

        $companiesQuery = Company::query()
            ->select('id', 'name')
            ->orderBy('name');

        if (!$user->can('approve company request')) {
            $companiesQuery->where('id', $user->company_id);
        }

        return Inertia::render('CompanyRequests/Index', [
            'companyRequests' => $query->get()->map(function ($companyRequest) {
                return [
                    'id' => $companyRequest->id,
                    'hashid' => Hashids::encode($companyRequest->id),
                    'reference' => $companyRequest->reference,
                    'status' => $companyRequest->status,
                    'notes' => $companyRequest->notes,
                    'start_date' => $this->formatDateValue($companyRequest->start_date),
                    'end_date' => $this->formatDateValue($companyRequest->end_date),
                    'duree_validite_formation' => $this->normalizeNullableInteger(
                        $companyRequest->duree_validite_formation
                    ),

                    'company' => $companyRequest->company ? [
                        'id' => $companyRequest->company->id,
                        'name' => $companyRequest->company->name,
                    ] : null,

                    'course' => $companyRequest->course ? [
                        'id' => $companyRequest->course->id,
                        'title' => $companyRequest->course->title,
                        'image' => $companyRequest->course->image,
                        'image_url' => $companyRequest->course->image_url ?? null,
                    ] : null,

                    'requested_by' => $companyRequest->requestedBy ? [
                        'id' => $companyRequest->requestedBy->id,
                        'name' => $companyRequest->requestedBy->name,
                        'email' => $companyRequest->requestedBy->email,
                    ] : null,

                    'accreditations' => $companyRequest->accreditations
                        ->map(fn ($accreditation) => $this->formatAccreditation($accreditation))
                        ->values(),

                    'learners_count' => $companyRequest->users->count(),
                    'created_at' => $companyRequest->created_at,
                    'validated_at' => $companyRequest->validated_at,
                ];
            }),

            'companies' => $companiesQuery->get()->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                ];
            }),

            'canApprove' => $user->can('approve company request'),
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        if (!$user->company_id) {
            return redirect()
                ->route('company-requests.index')
                ->with('error', 'Votre compte n’est lié à aucune entreprise.');
        }

        $categories = $this->getCategoriesWithCourses();

        $learners = User::query()
            ->where('company_id', $user->company_id)
            ->whereHas('roles', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['learner']);
            })
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($learner) {
                return [
                    'id' => $learner->id,
                    'name' => $learner->name,
                    'email' => $learner->email,
                    'photo_url' => $learner->profile_photo_url ?? null,
                ];
            });

        return Inertia::render('CompanyRequests/Create', [
            'categories' => $categories,
            'learners' => $learners,
            'accreditations' => $this->getActiveAccreditations(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'accreditation_ids' => ['required', 'array', 'min:1', 'max:2'],
            'accreditation_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('accreditations', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duree_validite_formation' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'learners' => ['required', 'array', 'min:1'],
            'learners.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'accreditation_ids.required' => 'Veuillez sélectionner au moins un agrément.',
            'accreditation_ids.min' => 'Veuillez sélectionner au moins un agrément.',
            'accreditation_ids.max' => 'Vous pouvez sélectionner au maximum deux agréments.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'duree_validite_formation.integer' => 'La durée de validité doit être un nombre entier de mois.',
            'duree_validite_formation.min' => 'La durée de validité doit être au minimum de 1 mois.',
            'duree_validite_formation.max' => 'La durée de validité ne peut pas dépasser 1200 mois.',
        ]);

        $user = auth()->user();

        if (!$user->company_id) {
            return back()->with('error', 'Votre compte n’est lié à aucune entreprise.');
        }

        $learnerIds = collect($validated['learners'])
            ->map(fn ($learnerId) => (int) $learnerId)
            ->unique()
            ->values()
            ->all();

        $validLearnersCount = User::query()
            ->whereIn('id', $learnerIds)
            ->where('company_id', $user->company_id)
            ->count();

        if ($validLearnersCount !== count($learnerIds)) {
            return back()->withErrors([
                'learners' => 'Certains learners ne sont pas liés à votre entreprise.',
            ]);
        }

        $companyRequest = DB::transaction(function () use ($validated, $user, $learnerIds) {
            $companyRequest = CompanyRequest::create([
                'company_id' => $user->company_id,
                'course_id' => (int) $validated['course_id'],
                'reference' => 'REQ-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(5)),
                'status' => 'pending',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'duree_validite_formation' => $this->normalizeNullableInteger(
                    $validated['duree_validite_formation'] ?? null
                ),
                'requested_by_id' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            $companyRequest->accreditations()->sync(
                collect($validated['accreditation_ids'])
                    ->map(fn ($accreditationId) => (int) $accreditationId)
                    ->unique()
                    ->values()
                    ->all()
            );

            $pivotData = collect($learnerIds)->mapWithKeys(function ($learnerId) {
                return [
                    $learnerId => [
                        'status' => 'pending',
                    ],
                ];
            })->toArray();

            $companyRequest->users()->sync($pivotData);

            return $companyRequest;
        });

        $this->sendCompanyRequestCreatedEmail($companyRequest);

        return redirect()
            ->route('company-requests.index')
            ->with('success', 'Demande envoyée avec succès.');
    }

    public function show($hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $user = auth()->user();

        $companyRequest = CompanyRequest::with([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ])->findOrFail($id);

        if (!$user->can('approve company request') && $companyRequest->company_id !== $user->company_id) {
            abort(403);
        }

        return Inertia::render('CompanyRequests/Show', [
            'companyRequest' => [
                'id' => $companyRequest->id,
                'hashid' => Hashids::encode($companyRequest->id),
                'reference' => $companyRequest->reference,
                'status' => $companyRequest->status,
                'notes' => $companyRequest->notes,
                'start_date' => $this->formatDateValue($companyRequest->start_date),
                'end_date' => $this->formatDateValue($companyRequest->end_date),
                'duree_validite_formation' => $this->normalizeNullableInteger(
                    $companyRequest->duree_validite_formation
                ),
                'created_at' => $companyRequest->created_at,
                'validated_at' => $companyRequest->validated_at,

                'company' => $companyRequest->company ? [
                    'id' => $companyRequest->company->id,
                    'name' => $companyRequest->company->name,
                    'email' => $companyRequest->company->email,
                ] : null,

                'course' => $companyRequest->course ? [
                    'id' => $companyRequest->course->id,
                    'title' => $companyRequest->course->title,
                    'description' => $companyRequest->course->description,
                    'image' => $companyRequest->course->image,
                    'image_url' => $companyRequest->course->image_url ?? null,
                    'category_id' => $companyRequest->course->category_id,
                    'category' => $companyRequest->course->category?->name,
                ] : null,

                'requested_by' => $companyRequest->requestedBy ? [
                    'id' => $companyRequest->requestedBy->id,
                    'name' => $companyRequest->requestedBy->name,
                    'email' => $companyRequest->requestedBy->email,
                ] : null,

                'validated_by' => $companyRequest->validatedBy ? [
                    'id' => $companyRequest->validatedBy->id,
                    'name' => $companyRequest->validatedBy->name,
                    'email' => $companyRequest->validatedBy->email,
                ] : null,

                'accreditations' => $companyRequest->accreditations
                    ->map(fn ($accreditation) => $this->formatAccreditation($accreditation))
                    ->values(),

                'learners' => $companyRequest->users->map(function ($learner) {
                    return [
                        'id' => $learner->id,
                        'name' => $learner->name,
                        'email' => $learner->email,
                        'photo_url' => $learner->profile_photo_url ?? null,
                        'status' => $learner->pivot->status,
                    ];
                })->values(),
            ],

            'canApprove' => $user->can('approve company request'),
        ]);
    }

    public function edit($hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $user = auth()->user();

        $companyRequest = CompanyRequest::with([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ])->findOrFail($id);

        $isSuperAdmin = $user->can('approve company request');

        if (!$isSuperAdmin && (int) $companyRequest->company_id !== (int) $user->company_id) {
            abort(403);
        }

        if (!$isSuperAdmin && $companyRequest->status !== 'pending') {
            return redirect()
                ->route('company-requests.show', [
                    'hashid' => Hashids::encode($companyRequest->id),
                ])
                ->with('error', 'Vous ne pouvez plus modifier cette demande après validation.');
        }

        $categories = $this->getCategoriesWithCourses();

        $attachedLearnerIds = $companyRequest->users
            ->pluck('id')
            ->map(fn ($learnerId) => (int) $learnerId)
            ->values()
            ->all();

        $learners = User::query()
            ->where('company_id', $companyRequest->company_id)
            ->whereHas('roles', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['learner']);
            })
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($learner) use ($companyRequest, $attachedLearnerIds) {
                $attachedToRequest = in_array(
                    (int) $learner->id,
                    $attachedLearnerIds,
                    true
                );

                return [
                    'id' => $learner->id,
                    'name' => $learner->name,
                    'email' => $learner->email,
                    'photo_url' => $learner->profile_photo_url ?? null,
                    'attached_to_request' => $attachedToRequest,
                    'started_course' => $attachedToRequest
                        ? $this->learnerStartedCourse(
                            (int) $learner->id,
                            (int) $companyRequest->course_id,
                            (int) $companyRequest->id
                        )
                        : false,
                ];
            })
            ->values();

        return Inertia::render('CompanyRequests/Edit', [
            'companyRequest' => [
                'id' => $companyRequest->id,
                'hashid' => Hashids::encode($companyRequest->id),
                'reference' => $companyRequest->reference,
                'status' => $companyRequest->status,
                'notes' => $companyRequest->notes,
                'course_id' => $companyRequest->course_id,
                'company_id' => $companyRequest->company_id,
                'start_date' => $this->formatDateValue($companyRequest->start_date),
                'end_date' => $this->formatDateValue($companyRequest->end_date),
                'duree_validite_formation' => $this->normalizeNullableInteger(
                    $companyRequest->duree_validite_formation
                ),
                'accreditation_ids' => $companyRequest->accreditations
                    ->pluck('id')
                    ->map(fn ($accreditationId) => (int) $accreditationId)
                    ->values(),
                'accreditations' => $companyRequest->accreditations
                    ->map(fn ($accreditation) => $this->formatAccreditation($accreditation))
                    ->values(),

                'company' => $companyRequest->company ? [
                    'id' => $companyRequest->company->id,
                    'name' => $companyRequest->company->name,
                ] : null,

                'course' => $companyRequest->course ? [
                    'id' => $companyRequest->course->id,
                    'title' => $companyRequest->course->title,
                    'category_id' => $companyRequest->course->category_id,
                    'category' => $companyRequest->course->category?->name,
                ] : null,

                'learners' => $companyRequest->users
                    ->map(function ($learner) use ($companyRequest) {
                        return [
                            'id' => $learner->id,
                            'name' => $learner->name,
                            'email' => $learner->email,
                            'photo_url' => $learner->profile_photo_url ?? null,
                            'status' => $learner->pivot->status,
                            'attached_to_request' => true,
                            'started_course' => $this->learnerStartedCourse(
                                (int) $learner->id,
                                (int) $companyRequest->course_id,
                                (int) $companyRequest->id
                            ),
                        ];
                    })
                    ->values(),
            ],

            'categories' => $categories,
            'learners' => $learners,
            'accreditations' => $this->getAccreditationsForRequest($companyRequest),
            'canApprove' => $isSuperAdmin,
            'canChangeCourse' => $companyRequest->status !== 'approved',
            'canChangeLearners' => $companyRequest->status !== 'approved'
                && ($isSuperAdmin || $companyRequest->status === 'pending'),
            'canChangeDetails' => $companyRequest->status !== 'approved',
        ]);
    }

    public function update(Request $request, $hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $user = auth()->user();

        $companyRequest = CompanyRequest::with([
            'users',
            'course',
            'accreditations',
        ])->findOrFail($id);

        $isSuperAdmin = $user->can('approve company request');
        $isApproved = $companyRequest->status === 'approved';

        if (!$isSuperAdmin && (int) $companyRequest->company_id !== (int) $user->company_id) {
            abort(403);
        }

        if (!$isSuperAdmin && $companyRequest->status !== 'pending') {
            return back()->withErrors([
                'message' => 'Vous ne pouvez plus modifier cette demande après validation.',
            ]);
        }

        $rules = [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($isApproved) {
            $rules['course_id'] = ['nullable', 'integer', 'exists:courses,id'];
            $rules['learners'] = ['nullable', 'array'];
            $rules['learners.*'] = ['required', 'integer', 'distinct', 'exists:users,id'];
            $rules['accreditation_ids'] = ['nullable', 'array', 'min:1', 'max:2'];
            $rules['accreditation_ids.*'] = ['required', 'integer', 'distinct', 'exists:accreditations,id'];
            $rules['start_date'] = ['nullable', 'date'];
            $rules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];
            $rules['duree_validite_formation'] = ['nullable', 'integer', 'min:1', 'max:1200'];
        } else {
            $rules['course_id'] = ['required', 'integer', 'exists:courses,id'];
            $rules['learners'] = ['required', 'array', 'min:1'];
            $rules['learners.*'] = ['required', 'integer', 'distinct', 'exists:users,id'];
            $rules['accreditation_ids'] = ['required', 'array', 'min:1', 'max:2'];
            $rules['accreditation_ids.*'] = [
                'required',
                'integer',
                'distinct',
                Rule::exists('accreditations', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ];
            $rules['start_date'] = ['required', 'date'];
            $rules['end_date'] = ['required', 'date', 'after_or_equal:start_date'];
            $rules['duree_validite_formation'] = ['nullable', 'integer', 'min:1', 'max:1200'];
        }

        $validated = $request->validate($rules, [
            'accreditation_ids.required' => 'Veuillez sélectionner au moins un agrément.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'duree_validite_formation.integer' => 'La durée de validité doit être un nombre entier de mois.',
            'duree_validite_formation.min' => 'La durée de validité doit être au minimum de 1 mois.',
            'duree_validite_formation.max' => 'La durée de validité ne peut pas dépasser 1200 mois.',
        ]);

        $oldLearnerIds = $companyRequest->users
            ->pluck('id')
            ->map(fn ($learnerId) => (int) $learnerId)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $oldAccreditationIds = $companyRequest->accreditations
            ->pluck('id')
            ->map(fn ($accreditationId) => (int) $accreditationId)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($isApproved) {
            $submittedCourseId = (int) $request->input(
                'course_id',
                $companyRequest->course_id
            );

            $submittedLearnerIds = collect(
                $request->input('learners', $oldLearnerIds)
            )
                ->map(fn ($learnerId) => (int) $learnerId)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $submittedAccreditationIds = collect(
                $request->input('accreditation_ids', $oldAccreditationIds)
            )
                ->map(fn ($accreditationId) => (int) $accreditationId)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $oldStartDate = $this->formatDateValue($companyRequest->start_date);
            $oldEndDate = $this->formatDateValue($companyRequest->end_date);
            $submittedStartDate = $this->formatDateValue(
                $request->input('start_date', $oldStartDate)
            );
            $submittedEndDate = $this->formatDateValue(
                $request->input('end_date', $oldEndDate)
            );

            $oldValidityDuration = $this->normalizeNullableInteger(
                $companyRequest->duree_validite_formation
            );

            $submittedValidityDuration = $this->normalizeNullableInteger(
                $request->input(
                    'duree_validite_formation',
                    $oldValidityDuration
                )
            );

            if ($submittedCourseId !== (int) $companyRequest->course_id) {
                return back()
                    ->withErrors([
                        'course_id' => 'Le cours ne peut pas être modifié après approbation.',
                    ])
                    ->withInput();
            }

            if ($submittedLearnerIds !== $oldLearnerIds) {
                return back()
                    ->withErrors([
                        'learners' => 'Les learners ne peuvent pas être ajoutés ou retirés après approbation.',
                    ])
                    ->withInput();
            }

            if ($submittedAccreditationIds !== $oldAccreditationIds) {
                return back()
                    ->withErrors([
                        'accreditation_ids' => 'Les agréments ne peuvent pas être modifiés après approbation.',
                    ])
                    ->withInput();
            }

            if ($submittedStartDate !== $oldStartDate || $submittedEndDate !== $oldEndDate) {
                return back()
                    ->withErrors([
                        'start_date' => 'La période de formation ne peut pas être modifiée après approbation.',
                    ])
                    ->withInput();
            }

            if ($submittedValidityDuration !== $oldValidityDuration) {
                return back()
                    ->withErrors([
                        'duree_validite_formation' => 'La durée de validité ne peut pas être modifiée après approbation.',
                    ])
                    ->withInput();
            }

            $companyRequest->update([
                'notes' => $validated['notes'] ?? $companyRequest->notes,
            ]);

            $companyRequest->refresh();
            $this->sendCompanyRequestUpdatedEmail($companyRequest);

            return redirect()
                ->route('company-requests.show', [
                    'hashid' => Hashids::encode($companyRequest->id),
                ])
                ->with('success', 'Notes de la demande modifiées avec succès.');
        }

        $newLearnerIds = collect($validated['learners'])
            ->map(fn ($learnerId) => (int) $learnerId)
            ->unique()
            ->values()
            ->all();

        $newAccreditationIds = collect($validated['accreditation_ids'])
            ->map(fn ($accreditationId) => (int) $accreditationId)
            ->unique()
            ->values()
            ->all();

        $validLearnersCount = User::query()
            ->whereIn('id', $newLearnerIds)
            ->where('company_id', $companyRequest->company_id)
            ->count();

        if ($validLearnersCount !== count($newLearnerIds)) {
            return back()->withErrors([
                'learners' => 'Certains learners ne sont pas liés à cette entreprise.',
            ]);
        }

        $originalCourseId = (int) $companyRequest->course_id;
        $removedLearnerIds = array_values(
            array_diff($oldLearnerIds, $newLearnerIds)
        );

        try {
            DB::transaction(function () use (
                $companyRequest,
                $validated,
                $newLearnerIds,
                $newAccreditationIds,
                $removedLearnerIds,
                $originalCourseId
            ) {
                foreach ($removedLearnerIds as $removedLearnerId) {
                    if ($this->learnerStartedCourse(
                        (int) $removedLearnerId,
                        $originalCourseId,
                        (int) $companyRequest->id
                    )) {
                        throw new \RuntimeException(
                            'Impossible de retirer un learner qui a déjà commencé ce cours dans cette demande.'
                        );
                    }
                }

                $companyRequest->update([
                    'course_id' => (int) $validated['course_id'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'duree_validite_formation' => $this->normalizeNullableInteger(
                        $validated['duree_validite_formation'] ?? null
                    ),
                    'notes' => $validated['notes'] ?? $companyRequest->notes,
                ]);

                $companyRequest->accreditations()->sync($newAccreditationIds);

                $pivotStatus = $companyRequest->status === 'rejected'
                    ? 'pending'
                    : $companyRequest->status;

                $pivotData = collect($newLearnerIds)
                    ->mapWithKeys(function ($learnerId) use ($pivotStatus) {
                        return [
                            $learnerId => [
                                'status' => $pivotStatus,
                            ],
                        ];
                    })
                    ->toArray();

                $companyRequest->users()->sync($pivotData);
            });
        } catch (Throwable $exception) {
            return back()
                ->withErrors([
                    'message' => $exception->getMessage(),
                ])
                ->withInput();
        }

        $companyRequest->refresh();
        $this->sendCompanyRequestUpdatedEmail($companyRequest);

        return redirect()
            ->route('company-requests.show', [
                'hashid' => Hashids::encode($companyRequest->id),
            ])
            ->with('success', 'Demande modifiée avec succès.');
    }

    public function approve($hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $companyRequest = CompanyRequest::with([
            'users',
            'course.category',
            'company',
            'requestedBy',
            'validatedBy',
            'accreditations',
        ])->findOrFail($id);

        if ($companyRequest->status !== 'pending') {
            return back()->withErrors([
                'message' => 'Cette demande est déjà traitée.',
            ]);
        }

        if ($companyRequest->accreditations->isEmpty()) {
            return back()->withErrors([
                'message' => 'La demande doit contenir au moins un agrément avant son approbation.',
            ]);
        }

        if (!$companyRequest->start_date || !$companyRequest->end_date) {
            return back()->withErrors([
                'message' => 'La période de formation doit être renseignée avant l’approbation.',
            ]);
        }

        DB::transaction(function () use ($companyRequest) {
            foreach ($companyRequest->users as $learner) {
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'user_id' => $learner->id,
                        'course_id' => $companyRequest->course_id,
                        'company_request_id' => $companyRequest->id,
                    ],
                    [
                        'company_id' => $companyRequest->company_id,
                        'access_type' => 'company',
                        'status' => 'not_started',
                        'progress_percent' => 0,
                        'is_approved' => true,
                        'attempts_rest' => 3,
                        'duree_validite_formation' => $this->normalizeNullableInteger(
                            $companyRequest->duree_validite_formation
                        ),
                    ]
                );

                /*
                |------------------------------------------------------------------
                | Synchronize an enrollment that already exists
                |------------------------------------------------------------------
                |
                | Existing progress, attempts and completion dates are preserved.
                |
                */
                if (!$enrollment->wasRecentlyCreated) {
                    $enrollment->update([
                        'company_id' => $companyRequest->company_id,
                        'access_type' => 'company',
                        'is_approved' => true,
                        'duree_validite_formation' => $this->normalizeNullableInteger(
                            $companyRequest->duree_validite_formation
                        ),
                    ]);
                }

                $companyRequest->users()->updateExistingPivot($learner->id, [
                    'status' => 'approved',
                ]);
            }

            $companyRequest->update([
                'status' => 'approved',
                'validated_by_id' => auth()->id(),
                'validated_at' => now(),
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Email notifications after successful transaction
        |--------------------------------------------------------------------------
        |
        | 1. Inform the company that the request was approved.
        | 2. Inform every learner that the course is available.
        |
        | Email failures are logged and never roll back the approval.
        |
        */
        $companyRequest->refresh();
        $companyRequest->load([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ]);

        $this->sendCompanyRequestApprovedEmail($companyRequest);
        $this->sendLearnerCourseAssignedEmails($companyRequest);

        return redirect()
            ->route('company-requests.show', [
                'hashid' => Hashids::encode($companyRequest->id),
            ])
            ->with(
                'success',
                'Demande approuvée, entreprise et learners notifiés par email.'
            );
    }

    public function reject(Request $request, $hashid)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $companyRequest = CompanyRequest::with([
            'users',
            'course.category',
            'company',
            'requestedBy',
            'validatedBy',
            'accreditations',
        ])->findOrFail($id);

        if ($companyRequest->status !== 'pending') {
            return back()->withErrors([
                'message' => 'Cette demande est déjà traitée.',
            ]);
        }

        DB::transaction(function () use ($companyRequest, $request) {
            foreach ($companyRequest->users as $learner) {
                $companyRequest->users()->updateExistingPivot($learner->id, [
                    'status' => 'rejected',
                ]);
            }

            $companyRequest->update([
                'status' => 'rejected',
                'validated_by_id' => auth()->id(),
                'validated_at' => now(),
                'notes' => $request->notes ?: $companyRequest->notes,
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Inform the company after successful rejection
        |--------------------------------------------------------------------------
        |
        | Learners are not emailed when the request is rejected because no course
        | enrollment is activated for them.
        |
        */
        $companyRequest->refresh();
        $companyRequest->load([
            'company',
            'course.category',
            'requestedBy',
            'validatedBy',
            'users',
            'accreditations',
        ]);

        $this->sendCompanyRequestRejectedEmail($companyRequest);

        return redirect()
            ->route('company-requests.show', [
                'hashid' => Hashids::encode($companyRequest->id),
            ])
            ->with(
                'success',
                'Demande refusée et entreprise notifiée par email.'
            );
    }

    /**
     * Notify the parent company when a new company request is created.
     *
     * Destination: COMPANY_REQUEST_PARENT_EMAIL from .env.
     */
    private function sendCompanyRequestCreatedEmail(CompanyRequest $companyRequest): void
    {
        $recipientEmail = config('services.company_requests.parent_email');

        if (!$recipientEmail) {
            Log::warning(
                'Company request creation email skipped because COMPANY_REQUEST_PARENT_EMAIL is not configured.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                ]
            );

            return;
        }

        try {
            $companyRequest->load([
                'company',
                'course.category',
                'requestedBy',
                'users',
                'accreditations',
            ]);

            app(MicrosoftOAuthMailer::class)->sendCompanyRequestCreated(
                $companyRequest,
                $recipientEmail
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send company request creation email.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                    'recipient' => $recipientEmail,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Notify the parent company when an existing company request is updated.
     *
     * Destination: COMPANY_REQUEST_PARENT_EMAIL from .env.
     */
    private function sendCompanyRequestUpdatedEmail(CompanyRequest $companyRequest): void
    {
        $recipientEmail = config('services.company_requests.parent_email');

        if (!$recipientEmail) {
            Log::warning(
                'Company request update email skipped because COMPANY_REQUEST_PARENT_EMAIL is not configured.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                ]
            );

            return;
        }

        try {
            $companyRequest->load([
                'company',
                'course.category',
                'requestedBy',
                'users',
                'accreditations',
            ]);

            app(MicrosoftOAuthMailer::class)->sendCompanyRequestUpdated(
                $companyRequest,
                $recipientEmail
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send company request update email.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                    'recipient' => $recipientEmail,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Notify the company that its request has been approved.
     *
     * Destination: companies.email.
     */
    private function sendCompanyRequestApprovedEmail(CompanyRequest $companyRequest): void
    {
        $recipientEmail = $companyRequest->company?->email;

        if (!$recipientEmail) {
            Log::warning(
                'Approved company request email skipped because the company has no email address.',
                [
                    'company_request_id' => $companyRequest->id,
                    'company_id' => $companyRequest->company_id,
                    'reference' => $companyRequest->reference,
                ]
            );

            return;
        }

        try {
            app(MicrosoftOAuthMailer::class)->sendCompanyRequestApproved(
                $companyRequest,
                $recipientEmail
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send company request approved email.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                    'recipient' => $recipientEmail,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Notify the company that its request has been rejected.
     *
     * Destination: companies.email.
     */
    private function sendCompanyRequestRejectedEmail(CompanyRequest $companyRequest): void
    {
        $recipientEmail = $companyRequest->company?->email;

        if (!$recipientEmail) {
            Log::warning(
                'Rejected company request email skipped because the company has no email address.',
                [
                    'company_request_id' => $companyRequest->id,
                    'company_id' => $companyRequest->company_id,
                    'reference' => $companyRequest->reference,
                ]
            );

            return;
        }

        try {
            app(MicrosoftOAuthMailer::class)->sendCompanyRequestRejected(
                $companyRequest,
                $recipientEmail
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send company request rejected email.',
                [
                    'company_request_id' => $companyRequest->id,
                    'reference' => $companyRequest->reference,
                    'recipient' => $recipientEmail,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Notify every learner after a company request has been approved.
     *
     * One failed learner email does not stop the emails for the other learners.
     */
    private function sendLearnerCourseAssignedEmails(CompanyRequest $companyRequest): void
    {
        $mailer = app(MicrosoftOAuthMailer::class);

        foreach ($companyRequest->users as $learner) {
            if (!$learner->email) {
                Log::warning(
                    'Learner course assignment email skipped because learner has no email.',
                    [
                        'company_request_id' => $companyRequest->id,
                        'learner_id' => $learner->id,
                    ]
                );

                continue;
            }

            try {
                $mailer->sendLearnerCourseAssigned(
                    $learner,
                    $companyRequest
                );
            } catch (Throwable $exception) {
                Log::error(
                    'Unable to send learner course assignment email.',
                    [
                        'company_request_id' => $companyRequest->id,
                        'reference' => $companyRequest->reference,
                        'learner_id' => $learner->id,
                        'learner_email' => $learner->email,
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

    private function getActiveAccreditations()
    {
        return Accreditation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($accreditation) => $this->formatAccreditation($accreditation))
            ->values();
    }

    private function getAccreditationsForRequest(CompanyRequest $companyRequest)
    {
        $selectedIds = $companyRequest->accreditations
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return Accreditation::query()
            ->where(function ($query) use ($selectedIds) {
                $query->where('is_active', true);

                if (!empty($selectedIds)) {
                    $query->orWhereIn('id', $selectedIds);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn ($accreditation) => $this->formatAccreditation($accreditation))
            ->values();
    }

    private function formatAccreditation(Accreditation $accreditation): array
    {
        return [
            'id' => $accreditation->id,
            'code' => $accreditation->code,
            'name' => $accreditation->name,
            'organization_name' => $accreditation->organization_name,
            'authority_name' => $accreditation->authority_name,
            'approval_reference' => $accreditation->approval_reference,
            'logo_path' => $accreditation->logo_path,
            'is_active' => (bool) $accreditation->is_active,
        ];
    }

    private function normalizeNullableInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function formatDateValue($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function getCategoriesWithCourses()
    {
        return Category::query()
            ->where('is_active', true)
            ->with([
                'courses' => function ($query) {
                    $query
                        ->where('is_published', true)
                        ->withCount(['chapters', 'slides'])
                        ->latest();
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'courses_count' => $category->courses->count(),
                    'courses' => $category->courses->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'hashid' => Hashids::encode($course->id),
                            'title' => $course->title,
                            'description' => $course->description,
                            'image' => $course->image,
                            'image_url' => $course->image_url ?? null,
                            'type' => $course->type,
                            'training_type' => $course->training_type,
                            'skill_level' => $course->skill_level,
                            'language' => $course->language,
                            'price' => $course->price,
                            'currency' => $course->currency,
                            'chapters_count' => $course->chapters_count,
                            'slides_count' => $course->slides_count,
                            'support_voice_over' => $course->support_voice_over,
                        ];
                    }),
                ];
            })
            ->filter(function ($category) {
                return $category['courses_count'] > 0;
            })
            ->values();
    }

    private function learnerStartedCourse(
        int $learnerId,
        int $courseId,
        int $companyRequestId
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Enrollment-specific start state
        |--------------------------------------------------------------------------
        |
        | The same learner can receive the same course several times. The request
        | identifier is therefore mandatory to prevent another enrollment from
        | locking the learner in the current company request.
        |
        */
        $enrollments = Enrollment::query()
            ->where('user_id', $learnerId)
            ->where('course_id', $courseId)
            ->where('company_request_id', $companyRequestId)
            ->get([
                'id',
                'status',
                'progress_percent',
                'started_at',
            ]);

        if ($enrollments->isEmpty()) {
            return false;        }

        $startedFromEnrollment = $enrollments->contains(function ($enrollment) {
            return $enrollment->status !== 'not_started'
                || (float) ($enrollment->progress_percent ?? 0) > 0
                || $enrollment->started_at !== null;
        });

        if ($startedFromEnrollment) {
            return true;
        }

        $enrollmentIds = $enrollments->pluck('id')->values();

        $hasSlideProgress = SlideProgress::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->where(function ($query) {
                $query
                    ->where('is_viewed', true)
                    ->orWhere('is_completed', true);
            })
            ->exists();

        if ($hasSlideProgress) {
            return true;
        }

        return ChapterProgress::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->where(function ($query) {
                $query
                    ->where('status', '!=', 'not_started')
                    ->orWhere('is_completed', true)
                    ->orWhere('progress_percent', '>', 0);
            })
            ->exists();
    }
}
