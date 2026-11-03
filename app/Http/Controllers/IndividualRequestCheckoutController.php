<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\IndividualRequest;
use App\Models\IndividualRequestCourse;
use App\Models\User;
use App\Services\MicrosoftOAuthMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class IndividualRequestCheckoutController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $learner = $this->learner($request);

        if (!$this->profileIsComplete($learner)) {
            return redirect()
                ->route('learner-account.edit')
                ->with(
                    'warning',
                    'Complete your learner profile before submitting a training request.'
                );
        }

        return Inertia::render('Public/IndividualRequests/Create', [
            'learner' => $this->formatLearner($learner),
            'coursesUrl' => route('courses'),
            'profileUrl' => route('learner-account.edit'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $learner = $this->learner($request);

        if (!$this->profileIsComplete($learner)) {
            return redirect()
                ->route('learner-account.edit')
                ->withErrors([
                    'profile' => __('Complete your learner profile before submitting the request.'),
                ]);
        }

        $validated = $request->validate(
            [
                'course_ids' => ['required', 'array', 'min:1', 'max:20'],
                'course_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                    'exists:courses,id',
                ],
                'learner_note' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'course_ids.required' => __('Please select at least one course.'),
                'course_ids.min' => __('Please select at least one course.'),
                'course_ids.max' => __('You cannot request more than 20 courses.'),
                'course_ids.*.distinct' => __('The same course cannot be selected more than once.'),
            ]
        );

        $courseIds = collect($validated['course_ids'])
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values();

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->where('is_published', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->keyBy('id');

        if ($courses->count() !== $courseIds->count()) {
            return back()
                ->withErrors([
                    'course_ids' => __('One or more selected courses are unavailable.'),
                ]);
        }

        $alreadyEnrolledCourseIds = Enrollment::query()
            ->where('user_id', $learner->id)
            ->whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->where('status', '!=', 'expired')
            ->where(function ($query) {
                $query
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->pluck('course_id')
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values();

        if ($alreadyEnrolledCourseIds->isNotEmpty()) {
            return back()->withErrors([
                'course_ids' => 'You already have access to: '
                    . $this->courseTitles($alreadyEnrolledCourseIds, $courses)
                    . '.',
            ]);
        }

        $pendingCourseIds = IndividualRequestCourse::query()
            ->whereIn('course_id', $courseIds)
            ->whereHas('individualRequest', function ($query) use ($learner) {
                $query
                    ->where('user_id', $learner->id)
                    ->where('status', IndividualRequest::STATUS_PENDING);
            })
            ->pluck('course_id')
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values();

        if ($pendingCourseIds->isNotEmpty()) {
            return back()->withErrors([
                'course_ids' => 'A pending request already exists for: '
                    . $this->courseTitles($pendingCourseIds, $courses)
                    . '.',
            ]);
        }

        $individualRequest = DB::transaction(function () use (
            $learner,
            $validated,
            $courseIds,
            $courses
        ) {
            $individualRequest = IndividualRequest::create([
                'reference' => $this->generateReference(),
                'user_id' => $learner->id,
                'status' => IndividualRequest::STATUS_PENDING,
                'learner_note' => $validated['learner_note'] ?? null,
                'submitted_at' => now(),
            ]);

            foreach ($courseIds as $courseId) {
                $course = $courses->get($courseId);

                $individualRequest->items()->create([
                    'course_id' => $course->id,
                    'price_snapshot' => $course->price ?? 0,
                    'currency_snapshot' => $course->currency ?: 'MAD',
                    'status' => IndividualRequestCourse::STATUS_PENDING,
                ]);
            }

            return $individualRequest;
        });

        /*
        |--------------------------------------------------------------------------
        | Notify LMS administrator
        |--------------------------------------------------------------------------
        |
        | The request is already saved at this point. Email failures are logged
        | and never cancel or roll back the learner's request.
        |
        */
        $this->sendIndividualRequestCreatedEmail(
            $individualRequest,
            $learner
        );

        return redirect()
            ->route(
                'public.individual-requests.success',
                ['reference' => $individualRequest->reference]
            )
            ->with(
                'success',
                'Your training request has been submitted successfully.'
            );
    }

    public function success(Request $request, string $reference): Response
    {
        $learner = $this->learner($request);

        $individualRequest = IndividualRequest::query()
            ->where('reference', $reference)
            ->where('user_id', $learner->id)
            ->withCount('items')
            ->firstOrFail();

        return Inertia::render('Public/IndividualRequests/Success', [
            'requestReference' => $individualRequest->reference,
            'status' => $individualRequest->status,
            'coursesCount' => (int) $individualRequest->items_count,
            'dashboardUrl' => route('learner-space.dashboard'),
            'coursesUrl' => route('courses'),
        ]);
    }

    private function learner(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyRole(['Learner']),
            403
        );

        return $user;
    }

    private function profileIsComplete(User $user): bool
    {
        return collect([
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->country,
            $user->date_of_birth,
            $user->place_of_birth,
            $user->passport_number,
            $user->passport_file,
        ])->every(fn ($value) => filled($value));
    }

    private function formatLearner(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => collect([
                $user->first_name,
                $user->middle_name,
                $user->last_name,
            ])->filter(fn ($value) => filled($value))->implode(' '),
            'email' => $user->email,
            'country' => $user->country,
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
            'place_of_birth' => $user->place_of_birth,
            'passport_number' => $user->passport_number,
            'profile_complete' => $this->profileIsComplete($user),
            'email_verified' => filled($user->email_verified_at),
        ];
    }

    private function courseTitles($courseIds, $courses): string
    {
        return collect($courseIds)
            ->map(fn ($courseId) => $courses->get((int) $courseId)?->title)
            ->filter()
            ->implode(', ');
    }

    /**
     * Notify the LMS administrator after an individual training request is created.
     *
     * Recipient configuration:
     * services.company_requests.parent_email
     * COMPANY_REQUEST_PARENT_EMAIL in .env
     */
    private function sendIndividualRequestCreatedEmail(
        IndividualRequest $individualRequest,
        User $learner
    ): void {
        $recipientEmail = config(
            'services.company_requests.parent_email'
        );

        if (!$recipientEmail) {
            Log::warning(
                'Individual request notification skipped because COMPANY_REQUEST_PARENT_EMAIL is not configured.',
                [
                    'individual_request_id' => $individualRequest->id,
                    'reference' => $individualRequest->reference,
                    'learner_id' => $learner->id,
                ]
            );

            return;
        }

        try {
            $individualRequest->load([
                'items.course',
            ]);

            app(MicrosoftOAuthMailer::class)->sendIndividualRequestCreated(
                $individualRequest,
                $learner,
                $recipientEmail
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to send individual request notification email.',
                [
                    'individual_request_id' => $individualRequest->id,
                    'reference' => $individualRequest->reference,
                    'learner_id' => $learner->id,
                    'learner_email' => $learner->email,
                    'recipient' => $recipientEmail,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'IND-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(10));
        } while (
            IndividualRequest::withTrashed()
                ->where('reference', $reference)
                ->exists()
        );

        return $reference;
    }
}
