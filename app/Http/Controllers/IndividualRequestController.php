<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\IndividualRequest;
use App\Models\IndividualRequestCourse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vinkla\Hashids\Facades\Hashids;

class IndividualRequestController extends Controller
{
    private const DEFAULT_EXAM_ATTEMPTS = 3;

    public function index(Request $request): Response
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    IndividualRequest::STATUS_PENDING,
                    IndividualRequest::STATUS_APPROVED,
                    IndividualRequest::STATUS_PARTIALLY_APPROVED,
                    IndividualRequest::STATUS_REJECTED,
                ]),
            ],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = $validated['status'] ?? null;

        $individualRequests = IndividualRequest::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email,country,status',
                'reviewedBy:id,first_name,middle_name,last_name,email',
            ])
            ->withCount([
                'items',
                'items as pending_courses_count' => function ($query) {
                    $query->where('status', IndividualRequestCourse::STATUS_PENDING);
                },
                'items as approved_courses_count' => function ($query) {
                    $query->where('status', IndividualRequestCourse::STATUS_APPROVED);
                },
                'items as rejected_courses_count' => function ($query) {
                    $query->where('status', IndividualRequestCourse::STATUS_REJECTED);
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('passport_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (IndividualRequest $individualRequest) {
                return [
                    'id' => $individualRequest->id,
                    'hashid' => Hashids::encode($individualRequest->id),
                    'reference' => $individualRequest->reference,
                    'status' => $individualRequest->status,
                    'learner_note' => $individualRequest->learner_note,
                    'submitted_at' => $individualRequest->submitted_at,
                    'reviewed_at' => $individualRequest->reviewed_at,
                    'items_count' => (int) $individualRequest->items_count,
                    'pending_courses_count' => (int) $individualRequest->pending_courses_count,
                    'approved_courses_count' => (int) $individualRequest->approved_courses_count,
                    'rejected_courses_count' => (int) $individualRequest->rejected_courses_count,
                    'learner' => $individualRequest->user
                        ? $this->formatUser($individualRequest->user)
                        : null,
                    'reviewed_by' => $individualRequest->reviewedBy
                        ? $this->formatUser($individualRequest->reviewedBy)
                        : null,
                ];
            });

        return Inertia::render('IndividualRequests/Index', [
            'individualRequests' => $individualRequests,
            'filters' => [
                'search' => $search,
                'status' => $status ?: '',
            ],
        ]);
    }

    public function show(string $hashid): Response
    {
        $this->authorizeSuperAdmin();

        $individualRequest = IndividualRequest::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email,country,date_of_birth,place_of_birth,passport_number,passport_file,status',
                'reviewedBy:id,first_name,middle_name,last_name,email',
                'items' => fn ($query) => $query->orderBy('id'),
                'items.course.category',
                'items.enrollment:id,individual_request_course_id,user_id,course_id,status,progress_percent,is_approved,attempts_rest',
            ])
            ->findOrFail($this->decodeHashid($hashid));

        return Inertia::render('IndividualRequests/Show', [
            'individualRequest' => $this->formatRequestForShow($individualRequest),
            'passportDownloadUrl' => filled($individualRequest->user?->passport_file)
                ? route('individual-requests.passport', $hashid)
                : null,
        ]);
    }

    public function review(Request $request, string $hashid): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:individual_request_courses,id',
            ],
            'decisions.*.status' => [
                'required',
                Rule::in([
                    IndividualRequestCourse::STATUS_APPROVED,
                    IndividualRequestCourse::STATUS_REJECTED,
                ]),
            ],
            'decisions.*.note' => ['nullable', 'string', 'max:1000'],
            'decisions.*.start_date' => ['nullable', 'date'],
            'decisions.*.end_date' => ['nullable', 'date'],
            'decisions.*.duree_validite_formation' => [
                'nullable',
                'integer',
                'min:1',
                'max:1200',
            ],
        ]);

        $requestId = $this->decodeHashid($hashid);
        $decisions = collect($validated['decisions']);

        DB::transaction(function () use (
            $requestId,
            $validated,
            $decisions
        ) {
            $individualRequest = IndividualRequest::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($individualRequest->status !== IndividualRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'request' => 'This request has already been reviewed.',
                ]);
            }

            $learner = $individualRequest->user;

            if (!$learner || !$learner->hasAnyRole(['Learner'])) {
                throw ValidationException::withMessages([
                    'learner' => 'The learner account linked to this request is unavailable.',
                ]);
            }

            $items = IndividualRequestCourse::query()
                ->with(['course', 'individualRequest'])
                ->where('individual_request_id', $individualRequest->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $expectedItemIds = $items
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            $receivedItemIds = $decisions
                ->pluck('item_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            if ($expectedItemIds->all() !== $receivedItemIds->all()) {
                throw ValidationException::withMessages([
                    'decisions' => 'A decision is required for every requested course.',
                ]);
            }

            foreach ($decisions as $decision) {
                $startDate = $decision['start_date'] ?? null;
                $endDate = $decision['end_date'] ?? null;

                if ($startDate && $endDate && $endDate < $startDate) {
                    throw ValidationException::withMessages([
                        'decisions' => 'The end date must be after or equal to the start date.',
                    ]);
                }
            }

            $approvedCount = $decisions
                ->where('status', IndividualRequestCourse::STATUS_APPROVED)
                ->count();

            $rejectedCount = $decisions
                ->where('status', IndividualRequestCourse::STATUS_REJECTED)
                ->count();

            foreach ($decisions as $decision) {
                $item = $items->get((int) $decision['item_id']);

                if (!$item) {
                    throw ValidationException::withMessages([
                        'decisions' => 'One selected course does not belong to this request.',
                    ]);
                }

                $item->update([
                    'status' => $decision['status'],
                    'admin_note' => $decision['note'] ?? null,
                    'start_date' => $decision['start_date'] ?? null,
                    'end_date' => $decision['end_date'] ?? null,
                    'duree_validite_formation' => $decision['duree_validite_formation'] ?? null,
                    'reviewed_by_id' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                if ($decision['status'] === IndividualRequestCourse::STATUS_APPROVED) {
                    $this->createEnrollment($learner, $item);
                }
            }

            $individualRequest->update([
                'status' => $this->resolveGlobalStatus(
                    approvedCount: $approvedCount,
                    rejectedCount: $rejectedCount,
                    totalCount: $items->count()
                ),
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_by_id' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        return redirect()
            ->route('individual-requests.show', $hashid)
            ->with('success', __('The individual request has been reviewed successfully.'));
    }

    public function downloadPassport(string $hashid): StreamedResponse
    {
        $this->authorizeSuperAdmin();

        $individualRequest = IndividualRequest::query()
            ->with('user')
            ->findOrFail($this->decodeHashid($hashid));

        $passportPath = $individualRequest->user?->passport_file;

        abort_unless(filled($passportPath), 404);
        abort_unless(Storage::disk('local')->exists($passportPath), 404);

        $extension = pathinfo($passportPath, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $passportPath,
            'passport-'
                . $individualRequest->reference
                . ($extension ? ".{$extension}" : '')
        );
    }

    private function createEnrollment(
        User $learner,
        IndividualRequestCourse $item
    ): Enrollment {
        $item->loadMissing(['course', 'individualRequest']);

        if (!$item->course) {
            throw ValidationException::withMessages([
                'course' => 'The approved course no longer exists.',
            ]);
        }

        $existingEnrollment = Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $item->course_id)
            ->where('is_approved', true)
            ->where(function ($query) {
                $query
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->lockForUpdate()
            ->first();

        if ($existingEnrollment) {
            throw ValidationException::withMessages([
                'course' => sprintf(
                    'The learner already has active access to "%s".',
                    $item->course->title
                ),
            ]);
        }

        $isScorm = strtolower(
            (string) ($item->course->content_type ?? 'normal')
        ) === 'scorm';

        $firstChapterId = $isScorm
            ? null
            : $item->course
                ->chapters()
                ->whereHas('slides', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderBy('sort_order')
                ->value('id');

        return Enrollment::create([
            'user_id' => $learner->id,
            'course_id' => $item->course_id,
            'company_id' => null,
            'company_request_id' => null,
            'individual_request_course_id' => $item->id,
            'current_chapter_id' => $firstChapterId,
            'access_type' => 'individual',
            'status' => 'not_started',
            'started_at' => null,
            'completed_at' => null,
            'expired_at' => null,
            'progress_percent' => 0,
            'comment' => 'Created from individual request '
                . $item->individualRequest->reference,
            'is_approved' => true,
            'attempts_rest' => self::DEFAULT_EXAM_ATTEMPTS,
            'duree_validite_formation' => $item->duree_validite_formation,
        ]);
    }

    private function resolveGlobalStatus(
        int $approvedCount,
        int $rejectedCount,
        int $totalCount
    ): string {
        if ($totalCount > 0 && $approvedCount === $totalCount) {
            return IndividualRequest::STATUS_APPROVED;
        }

        if ($totalCount > 0 && $rejectedCount === $totalCount) {
            return IndividualRequest::STATUS_REJECTED;
        }

        return IndividualRequest::STATUS_PARTIALLY_APPROVED;
    }

    private function formatRequestForShow(
        IndividualRequest $individualRequest
    ): array {
        return [
            'id' => $individualRequest->id,
            'hashid' => Hashids::encode($individualRequest->id),
            'reference' => $individualRequest->reference,
            'status' => $individualRequest->status,
            'learner_note' => $individualRequest->learner_note,
            'admin_note' => $individualRequest->admin_note,
            'submitted_at' => $individualRequest->submitted_at,
            'reviewed_at' => $individualRequest->reviewed_at,
            'learner' => $individualRequest->user
                ? array_merge(
                    $this->formatUser($individualRequest->user),
                    [
                        'date_of_birth' => $individualRequest->user->date_of_birth?->format('Y-m-d'),
                        'place_of_birth' => $individualRequest->user->place_of_birth,
                        'passport_number' => $individualRequest->user->passport_number,
                        'has_passport_file' => filled($individualRequest->user->passport_file),
                    ]
                )
                : null,
            'reviewed_by' => $individualRequest->reviewedBy
                ? $this->formatUser($individualRequest->reviewedBy)
                : null,
            'items' => $individualRequest->items
                ->map(function (IndividualRequestCourse $item) {
                    return [
                        'id' => $item->id,
                        'status' => $item->status,
                        'price_snapshot' => (float) $item->price_snapshot,
                        'currency_snapshot' => $item->currency_snapshot,
                        'admin_note' => $item->admin_note,
                        'start_date' => $item->start_date?->format('Y-m-d'),
                        'end_date' => $item->end_date?->format('Y-m-d'),
                        'duree_validite_formation' => $item->duree_validite_formation,
                        'reviewed_at' => $item->reviewed_at,
                        'course' => $item->course
                            ? [
                                'id' => $item->course->id,
                                'hashid' => $item->course->hashid
                                    ?? Hashids::encode($item->course->id),
                                'title' => $item->course->title,
                                'ref' => $item->course->ref,
                                'content_type' => $item->course->content_type ?? 'normal',
                                'training_type' => $item->course->training_type,
                                'image_url' => $item->course->image_url
                                    ?? ($item->course->image
                                        ? asset('storage/' . $item->course->image)
                                        : null),
                                'category' => $item->course->category
                                    ? [
                                        'id' => $item->course->category->id,
                                        'name' => $item->course->category->name,
                                    ]
                                    : null,
                            ]
                            : null,
                        'enrollment' => $item->enrollment
                            ? [
                                'id' => $item->enrollment->id,
                                'hashid' => $item->enrollment->hashid
                                    ?? Hashids::encode($item->enrollment->id),
                                'status' => $item->enrollment->status,
                                'progress_percent' => (float) $item->enrollment->progress_percent,
                                'attempts_rest' => (int) ($item->enrollment->attempts_rest ?? 0),
                            ]
                            : null,
                    ];
                })
                ->values(),
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => collect([
                $user->first_name,
                $user->middle_name,
                $user->last_name,
            ])->filter(fn ($value) => filled($value))->implode(' '),
            'email' => $user->email,
            'country' => $user->country,
            'status' => $user->status,
        ];
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['Super-admin']),
            403
        );
    }

    private function decodeHashid(string $hashid): int
    {
        $decoded = Hashids::decode($hashid);

        if (empty($decoded)) {
            abort(404);
        }

        return (int) $decoded[0];
    }
}
