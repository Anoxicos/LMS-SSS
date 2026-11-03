<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\Slide;
use App\Models\SlideProgress;
use App\Models\ChapterProgress;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Services\MicrosoftOAuthMailer;
use Throwable;

class LearnerSpaceController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $enrollments = Enrollment::with([
                'course.category',
                'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
                'scormTracking',
                'exams' => function ($query) {
                    $query->where(function ($examQuery) {
                        $examQuery
                            ->where('passed', true)
                            ->orWhere('status', 'passed');
                    });
                },
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Use the same effective progress everywhere
        |--------------------------------------------------------------------------
        |
        | SCORM progress is stored primarily in scorm_trackings. Building the
        | dashboard statistics from the raw enrollments table can therefore show
        | an old percentage or an old status. Format the records first, then use
        | the normalized values for both statistics and recent courses.
        |
        */
        $formattedEnrollments = $enrollments
            ->map(function ($enrollment) {
                return $this->formatEnrollmentListItem($enrollment);
            })
            ->values();

        return Inertia::render('learner/espace/dashboard', [
            'stats' => [
                'total_courses' => $formattedEnrollments->count(),
                'not_started' => $formattedEnrollments->where('status', 'not_started')->count(),
                'in_progress' => $formattedEnrollments->where('status', 'in_progress')->count(),
                'completed' => $formattedEnrollments->where('status', 'completed')->count(),
                'expired' => $formattedEnrollments->where('status', 'expired')->count(),
            ],
            'recentCourses' => $formattedEnrollments
                ->take(5)
                ->values(),
        ]);
    }

    public function myCourses()
    {
        $user = auth()->user();

        $enrollments = Enrollment::with([
                'course.category',
                'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
                'scormTracking',
                'exams' => function ($query) {
                    $query->where(function ($examQuery) {
                        $examQuery
                            ->where('passed', true)
                            ->orWhere('status', 'passed');
                    });
                },
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($enrollment) {
                return $this->formatEnrollmentListItem($enrollment);
            })
            ->values();

        return Inertia::render('learner/espace/myCourses', [
            'enrollments' => $enrollments,
        ]);
    }
    public function courseViewer($enrollmentHashid)
    {
        $user = auth()->user();

        $enrollmentId = $this->decodeHashid($enrollmentHashid);

        $enrollment = Enrollment::with([
                'course.category',
                'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
            ])
            ->where('id', $enrollmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$enrollment->course) {
            abort(404, __('Course not found.'));
        }

        $expiration = $this->getEnrollmentExpirationState($enrollment);

        if ($expiration['is_expired']) {
            return redirect()
                ->route(
                    'learner-space.course-finish',
                    $enrollment->hashid ?? $enrollmentHashid
                )
                ->with(
                    'error',
                    sprintf(
                        'This training validity expired on %s.',
                        Carbon::parse($expiration['expired_at'])->format('d-m-Y')
                    )
                );
        }

        $hasPassedExam = Exam::where('enrollment_id', $enrollment->id)
            ->where(function ($query) {
                $query
                    ->where('passed', true)
                    ->orWhere('status', 'passed');
            })
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Final course access lock
        |--------------------------------------------------------------------------
        */
        if (
            (int) ($enrollment->attempts_rest ?? 0) <= 0 ||
            $hasPassedExam
        ) {
            $message = $hasPassedExam
                ? 'The final exam has already been passed. Course access is now closed.'
                : 'No exam attempts remaining. Course access is now closed.';

            return redirect()
                ->route(
                    'learner-space.course-finish',
                    $enrollment->hashid ?? $enrollmentHashid
                )
                ->with('error', $message);
        }

        $trainingAccess = $this->getTrainingAccessState($enrollment);

        if (!$trainingAccess['can_access_course']) {
            return redirect()
                ->route('learner-space.my-courses')
                ->with('error', $trainingAccess['blocked_reason']);
        }

        if ($enrollment->status === 'not_started') {
            $enrollment->update([
                'status' => 'in_progress',
                'started_at' => $enrollment->started_at ?? now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Dedicated SCORM viewer
        |--------------------------------------------------------------------------
        |
        | SCORM packages communicate their progress to the LMS through the
        | JavaScript SCORM API exposed by the dedicated viewer page.
        |
        */
        if ($this->isScormCourse($enrollment->course)) {
            return Inertia::render('learner/espace/scormCourseViewer', [
                'enrollment' => $this->formatScormEnrollmentForViewer($enrollment),
            ]);
        }

        $enrollment->load($this->viewerRelations());

        return Inertia::render('learner/espace/courseViewer', [
            'enrollment' => $this->formatEnrollmentForViewer($enrollment),
        ]);
    }

    public function markSlideViewed($enrollmentHashid, Slide $slide)
    {
        return $this->updateSlideProgress($enrollmentHashid, $slide, false);
    }

    public function markSlideCompleted($enrollmentHashid, Slide $slide)
    {
        return $this->updateSlideProgress($enrollmentHashid, $slide, true);
    }

    private function updateSlideProgress($enrollmentHashid, Slide $slide, bool $completed)
    {
        $user = auth()->user();

        $enrollmentId = $this->decodeHashid($enrollmentHashid);

        $enrollment = Enrollment::with([
                'course.chapters' => function ($query) {
                    $query->whereHas('slides', function ($slideQuery) {
                            $slideQuery->where('is_active', true);
                        })
                        ->orderBy('sort_order')
                        ->with(['slides' => function ($slideQuery) {
                            $slideQuery->where('is_active', true)
                                ->orderBy('sort_order');
                        }]);
                },
                'slideProgress',
                'chapterProgress',
            ])
            ->where('id', $enrollmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $hasPassedExam = Exam::where('enrollment_id', $enrollment->id)
            ->where(function ($query) {
                $query
                    ->where('passed', true)
                    ->orWhere('status', 'passed');
            })
            ->exists();

        if (
            (int) ($enrollment->attempts_rest ?? 0) <= 0 ||
            $hasPassedExam
        ) {
            abort(403, 'Course access is closed for this enrollment.');
        }

        $trainingAccess = $this->getTrainingAccessState($enrollment);

        if (!$trainingAccess['can_access_course']) {
            abort(403, $trainingAccess['blocked_reason']);
        }

        $slide->load('chapter.slides');

        if (!$slide->chapter || (int) $slide->chapter->course_id !== (int) $enrollment->course_id) {
            abort(403, 'This slide does not belong to this enrollment course.');
        }

        if (!(bool) $slide->is_active) {
            abort(403, 'This slide is not active.');
        }

        if (!$this->canAccessSlide($enrollment, $slide)) {
            abort(403, 'You must complete previous slides first.');
        }

        DB::transaction(function () use ($enrollment, $slide, $completed) {
            $existingProgress = SlideProgress::where('enrollment_id', $enrollment->id)
                ->where('slide_id', $slide->id)
                ->first();

            if ($completed) {
                SlideProgress::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'slide_id' => $slide->id,
                    ],
                    [
                        'is_viewed' => true,
                        'is_completed' => true,
                        'completed_at' => $existingProgress?->completed_at ?? now(),
                    ]
                );
            } else {
                SlideProgress::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'slide_id' => $slide->id,
                    ],
                    [
                        'is_viewed' => true,
                        'is_completed' => (bool) ($existingProgress?->is_completed ?? false),
                        'completed_at' => $existingProgress?->completed_at,
                    ]
                );
            }

            $this->recalculateChapterProgress($enrollment, $slide);
            $this->recalculateCourseProgress($enrollment, $slide);
        });

        return back()->with('success', __('Progress updated successfully.'));
    }

    private function recalculateChapterProgress(Enrollment $enrollment, Slide $slide): void
    {
        $chapter = $slide->chapter;

        if (!$chapter) {
            return;
        }

        $slideIds = $chapter->slides()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id');

        $totalSlides = $slideIds->count();

        $completedSlides = SlideProgress::where('enrollment_id', $enrollment->id)
            ->whereIn('slide_id', $slideIds)
            ->where('is_completed', true)
            ->count();

        $chapterPercent = $totalSlides > 0
            ? round(($completedSlides / $totalSlides) * 100, 2)
            : 0;

        $chapterProgress = ChapterProgress::firstOrNew([
            'enrollment_id' => $enrollment->id,
            'chapter_id' => $chapter->id,
        ]);

        $chapterProgress->last_slide_id = $slide->id;
        $chapterProgress->status = $chapterPercent >= 100 ? 'completed' : 'in_progress';
        $chapterProgress->started_at = $chapterProgress->started_at ?? now();
        $chapterProgress->is_completed = $chapterPercent >= 100;
        $chapterProgress->progress_percent = $chapterPercent;
        $chapterProgress->save();
    }

    private function recalculateCourseProgress(Enrollment $enrollment, Slide $slide): void
    {
        $enrollment->load([
            'course.chapters' => function ($query) {
                $query->whereHas('slides', function ($slideQuery) {
                        $slideQuery->where('is_active', true);
                    })
                    ->orderBy('sort_order')
                    ->with(['slides' => function ($slideQuery) {
                        $slideQuery->where('is_active', true)
                            ->orderBy('sort_order');
                    }]);
            },
        ]);

        $validChapters = $enrollment->course?->chapters ?? collect();

        $allSlideIds = $validChapters
            ->flatMap(function ($chapter) {
                return $chapter->slides->pluck('id');
            })
            ->values();

        $totalCourseSlides = $allSlideIds->count();

        $completedCourseSlides = SlideProgress::where('enrollment_id', $enrollment->id)
            ->whereIn('slide_id', $allSlideIds)
            ->where('is_completed', true)
            ->count();

        $coursePercent = $totalCourseSlides > 0
            ? round(($completedCourseSlides / $totalCourseSlides) * 100, 2)
            : 0;

        $enrollment->update([
            'current_chapter_id' => $slide->chapter_id,
            'progress_percent' => $coursePercent,
            'status' => $coursePercent >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $coursePercent >= 100
                ? ($enrollment->completed_at ?? now())
                : null,
        ]);
    }

    private function canAccessSlide(Enrollment $enrollment, Slide $targetSlide): bool
    {
        $slides = $this->getOrderedCourseSlides($enrollment);

        if ($slides->isEmpty()) {
            return false;
        }

        $targetIndex = $slides->search(function ($slide) use ($targetSlide) {
            return (int) $slide->id === (int) $targetSlide->id;
        });

        if ($targetIndex === false) {
            return false;
        }

        if ($targetIndex === 0) {
            return true;
        }

        $previousSlide = $slides[$targetIndex - 1];

        return SlideProgress::where('enrollment_id', $enrollment->id)
            ->where('slide_id', $previousSlide->id)
            ->where('is_completed', true)
            ->exists();
    }

    private function getOrderedCourseSlides(Enrollment $enrollment)
    {
        $enrollment->loadMissing([
            'course.chapters' => function ($query) {
                $query->whereHas('slides', function ($slideQuery) {
                        $slideQuery->where('is_active', true);
                    })
                    ->orderBy('sort_order')
                    ->with(['slides' => function ($slideQuery) {
                        $slideQuery->where('is_active', true)
                            ->orderBy('sort_order');
                    }]);
            },
        ]);

        return ($enrollment->course?->chapters ?? collect())
            ->flatMap(function ($chapter) {
                return $chapter->slides;
            })
            ->values();
    }

    private function viewerRelations(): array
    {
        return [
            'course.category',
            'companyRequest.accreditations',
            'course.chapters' => function ($query) {
                $query->whereHas('slides', function ($slideQuery) {
                        $slideQuery->where('is_active', true);
                    })
                    ->orderBy('sort_order')
                    ->with([
                        'slides' => function ($slideQuery) {
                            $slideQuery->where('is_active', true)
                                ->orderBy('sort_order')
                                ->with(['questions' => function ($questionQuery) {
                                    $questionQuery->orderBy('sort_order');
                                }]);
                        },
                    ]);
            },
            'chapterProgress',
            'slideProgress',
        ];
    }

    private function getLastSlideIdFromDatabase(Enrollment $enrollment): ?int
    {
        $lastChapterProgress = $enrollment->chapterProgress
            ->filter(function ($progress) {
                return !empty($progress->last_slide_id);
            })
            ->sortByDesc(function ($progress) {
                return $progress->updated_at ?? $progress->started_at ?? $progress->id;
            })
            ->first();

        return $lastChapterProgress?->last_slide_id
            ? (int) $lastChapterProgress->last_slide_id
            : null;
    }

    private function calculateEnrollmentExpirationDate(Enrollment $enrollment, $referenceDate): ?Carbon 
    {
        $validityMonths = $enrollment->duree_validite_formation;

        if (
            $validityMonths === null ||
            (int) $validityMonths <= 0 ||
            !$referenceDate
        ) {
            return null;
        }

        return Carbon::parse($referenceDate)
            ->copy()
            ->addMonthsNoOverflow((int) $validityMonths)
            ->endOfDay();
    }

    private function synchronizeEnrollmentExpiration(Enrollment $enrollment, $referenceDate): ?Carbon 
    {
        $expirationDate = $this->calculateEnrollmentExpirationDate(
            $enrollment,
            $referenceDate
        );

        $currentExpiration = $enrollment->expired_at
            ? Carbon::parse($enrollment->expired_at)
            : null;

        $expirationChanged = $currentExpiration?->toDateTimeString()
            !== $expirationDate?->toDateTimeString();

        if ($expirationChanged) {
            $enrollment->forceFill([
                'expired_at' => $expirationDate,
            ])->save();
        }

        return $expirationDate;
    }

    private function getEnrollmentExpirationState(Enrollment $enrollment): array {
        $validityMonths = $enrollment->duree_validite_formation !== null
            ? (int) $enrollment->duree_validite_formation
            : null;

        $hasExpiration = $validityMonths !== null && $validityMonths > 0;
        $expiredAt = $enrollment->expired_at
            ? Carbon::parse($enrollment->expired_at)
            : null;

        $isExpired = $expiredAt
            ? now()->greaterThan($expiredAt)
            : false;

        $remainingDays = $expiredAt
            ? (int) floor(now()->diffInDays($expiredAt, false))
            : null;

        $isExpiringSoon = (
            !$isExpired &&
            $remainingDays !== null &&
            $remainingDays >= 0 &&
            $remainingDays <= 30
        );

        $state = 'no_expiration';

        if ($hasExpiration && !$expiredAt) {
            $state = 'pending';
        } elseif ($isExpired) {
            $state = 'expired';
        } elseif ($expiredAt) {
            $state = $isExpiringSoon ? 'expiring_soon' : 'valid';
        }

        return [
            'duration_months' => $validityMonths,
            'has_expiration' => $hasExpiration,
            'expired_at' => $expiredAt?->toIso8601String(),
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'remaining_days' => $remainingDays,
            'state' => $state,
        ];
    }

    private function getTrainingAccessState(Enrollment $enrollment): array
    {
        $enrollment->loadMissing([
            'companyRequest.accreditations',
            'individualRequestCourse.individualRequest',
        ]);

        $expiration = $this->getEnrollmentExpirationState($enrollment);

        if ($expiration['is_expired']) {
            return [
                'state' => 'expired',
                'can_access_course' => false,
                'can_start_exam' => false,
                'blocked_reason' => sprintf(
                    'This training validity expired on %s.',
                    Carbon::parse($expiration['expired_at'])->format('d-m-Y')
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Individual request training period
        |--------------------------------------------------------------------------
        */
        $individualItem = $enrollment->individualRequestCourse;

        if ($individualItem) {
            $startDate = $individualItem->start_date
                ? Carbon::parse($individualItem->start_date)->startOfDay()
                : null;

            $endDate = $individualItem->end_date
                ? Carbon::parse($individualItem->end_date)->endOfDay()
                : null;

            $now = now();
            $isUpcoming = $startDate && $now->lt($startDate);
            $isEnded = $endDate && $now->gt($endDate);

            if ($isUpcoming) {
                return [
                    'state' => 'upcoming',
                    'can_access_course' => false,
                    'can_start_exam' => false,
                    'blocked_reason' => sprintf(
                        'This training will be available from %s.',
                        $startDate->format('d-m-Y')
                    ),
                ];
            }

            if ($isEnded) {
                return [
                    'state' => 'ended',
                    'can_access_course' => false,
                    'can_start_exam' => false,
                    'blocked_reason' => sprintf(
                        'The training period ended on %s.',
                        $endDate->format('d-m-Y')
                    ),
                ];
            }

            return [
                'state' => 'active',
                'can_access_course' => true,
                'can_start_exam' => true,
                'blocked_reason' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Company request training period
        |--------------------------------------------------------------------------
        */
        $companyRequest = $enrollment->companyRequest;

        if (!$companyRequest) {
            return [
                'state' => 'unrestricted',
                'can_access_course' => true,
                'can_start_exam' => true,
                'blocked_reason' => null,
            ];
        }

        $startDate = $companyRequest->start_date
            ? Carbon::parse($companyRequest->start_date)->startOfDay()
            : null;

        $endDate = $companyRequest->end_date
            ? Carbon::parse($companyRequest->end_date)->endOfDay()
            : null;

        $now = now();
        $isUpcoming = $startDate && $now->lt($startDate);
        $isEnded = $endDate && $now->gt($endDate);

        if ($isUpcoming) {
            return [
                'state' => 'upcoming',
                'can_access_course' => false,
                'can_start_exam' => false,
                'blocked_reason' => sprintf(
                    'This training will be available from %s.',
                    $startDate->format('d-m-Y')
                ),
            ];
        }

        if ($isEnded) {
            return [
                'state' => 'ended',
                'can_access_course' => false,
                'can_start_exam' => false,
                'blocked_reason' => sprintf(
                    'The training period ended on %s.',
                    $endDate->format('d-m-Y')
                ),
            ];
        }

        return [
            'state' => 'active',
            'can_access_course' => true,
            'can_start_exam' => true,
            'blocked_reason' => null,
        ];
    }

    private function formatTrainingRequest(Enrollment $enrollment): ?array
    {
        $enrollment->loadMissing([
            'companyRequest.accreditations',
            'individualRequestCourse.individualRequest',
        ]);

        $access = $this->getTrainingAccessState($enrollment);
        $individualItem = $enrollment->individualRequestCourse;

        if ($individualItem && $individualItem->individualRequest) {
            $individualRequest = $individualItem->individualRequest;

            return [
                'type' => 'individual',
                'id' => $individualRequest->id,
                'hashid' => Hashids::encode($individualRequest->id),
                'reference' => $individualRequest->reference,
                'item_id' => $individualItem->id,
                'item_status' => $individualItem->status,
                'start_date' => $individualItem->start_date
                    ? Carbon::parse($individualItem->start_date)->format('Y-m-d')
                    : null,
                'end_date' => $individualItem->end_date
                    ? Carbon::parse($individualItem->end_date)->format('Y-m-d')
                    : null,
                'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                    ? (int) $enrollment->duree_validite_formation
                    : null,
                'expired_at' => $enrollment->expired_at?->toIso8601String(),
                'expiration' => $this->getEnrollmentExpirationState($enrollment),
                'access_state' => $access['state'],
                'can_access_course' => $access['can_access_course'],
                'can_start_exam' => $access['can_start_exam'],
                'blocked_reason' => $access['blocked_reason'],
                'accreditations' => [],
            ];
        }

        $companyRequest = $enrollment->companyRequest;

        if (!$companyRequest) {
            return null;
        }

        return [
            'type' => 'company',
            'id' => $companyRequest->id,
            'hashid' => Hashids::encode($companyRequest->id),
            'reference' => $companyRequest->reference,
            'start_date' => $companyRequest->start_date
                ? Carbon::parse($companyRequest->start_date)->format('Y-m-d')
                : null,
            'end_date' => $companyRequest->end_date
                ? Carbon::parse($companyRequest->end_date)->format('Y-m-d')
                : null,
            'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                ? (int) $enrollment->duree_validite_formation
                : null,
            'expired_at' => $enrollment->expired_at?->toIso8601String(),
            'expiration' => $this->getEnrollmentExpirationState($enrollment),
            'access_state' => $access['state'],
            'can_access_course' => $access['can_access_course'],
            'can_start_exam' => $access['can_start_exam'],
            'blocked_reason' => $access['blocked_reason'],
            'accreditations' => $companyRequest->accreditations
                ->map(function ($accreditation) {
                    return [
                        'id' => $accreditation->id,
                        'code' => strtolower((string) $accreditation->code),
                        'name' => $accreditation->name,
                        'organization_name' => $accreditation->organization_name,
                        'authority_name' => $accreditation->authority_name,
                        'approval_reference' => $accreditation->approval_reference,
                    ];
                })
                ->values(),
        ];
    }

    private function formatEnrollmentForViewer(Enrollment $enrollment): array
    {
        $course = $enrollment->course;
        $currentSlideId = $this->getLastSlideIdFromDatabase($enrollment);

        $slideProgress = $enrollment->slideProgress
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'enrollment_id' => $progress->enrollment_id,
                    'slide_id' => $progress->slide_id,
                    'is_viewed' => (bool) $progress->is_viewed,
                    'is_completed' => (bool) $progress->is_completed,
                    'completed_at' => $progress->completed_at,
                    'created_at' => $progress->created_at,
                    'updated_at' => $progress->updated_at,
                ];
            })
            ->values();

        $chapterProgress = $enrollment->chapterProgress
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'enrollment_id' => $progress->enrollment_id,
                    'chapter_id' => $progress->chapter_id,
                    'last_slide_id' => $progress->last_slide_id,
                    'status' => $progress->status,
                    'started_at' => $progress->started_at,
                    'is_completed' => (bool) $progress->is_completed,
                    'progress_percent' => (float) $progress->progress_percent,
                    'created_at' => $progress->created_at,
                    'updated_at' => $progress->updated_at,
                ];
            })
            ->values();

        $chapters = ($course?->chapters ?? collect())
            ->filter(function ($chapter) {
                return $chapter->slides && $chapter->slides->count() > 0;
            })
            ->values()
            ->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'sort_order' => $chapter->sort_order,

                    'slides' => $chapter->slides
                        ->where('is_active', true)
                        ->sortBy('sort_order')
                        ->values()
                        ->map(function ($slide) {
                            return [
                                'id' => $slide->id,
                                'chapter_id' => $slide->chapter_id,
                                'title' => $slide->title,
                                'lesson_type' => $slide->lesson_type,
                                'content' => $slide->content,

                                'video_url' => $slide->video_url,
                                'video_duration' => $slide->video_duration ?? null,

                                'file_path' => $slide->file_path,
                                'file_url' => $slide->file_path
                                    ? asset('storage/' . $slide->file_path)
                                    : null,
                                'file_name' => $slide->file_name,
                                'file_type' => $slide->file_type,
                                'file_size' => $slide->file_size ?? null,

                                'external_url' => $slide->external_url,

                                'is_preview' => (bool) $slide->is_preview,
                                'is_mandatory' => (bool) $slide->is_mandatory,
                                'is_downloadable' => (bool) ($slide->is_downloadable ?? false),
                                'is_active' => (bool) $slide->is_active,
                                'sort_order' => $slide->sort_order,

                                'questions' => $slide->questions
                                    ? $slide->questions
                                        ->sortBy('sort_order')
                                        ->values()
                                        ->map(function ($question) {
                                            return [
                                                'id' => $question->id,
                                                'slide_id' => $question->slide_id,
                                                'question_type' => $question->question_type ?: 'qcm',
                                                'question' => $question->question,
                                                'option_a' => $question->option_a,
                                                'option_b' => $question->option_b,
                                                'option_c' => $question->option_c,
                                                'correct_answer' => strtolower((string) $question->correct_answer),
                                                'explanation' => $question->explanation,
                                                'sort_order' => $question->sort_order,
                                            ];
                                        })
                                        ->values()
                                    : [],

                                'voice_over_enabled' => (bool) $slide->voice_over_enabled,
                                'voice_over_text' => $slide->voice_over_text,
                                'voice_over_lang' => $slide->voice_over_lang,
                                'voice_over_voice' => $slide->voice_over_voice,
                                'voice_over_audio_path' => $slide->voice_over_audio_path,
                                'voice_over_audio_url' => $slide->voice_over_audio_path
                                    ? asset('storage/' . $slide->voice_over_audio_path)
                                    : null,
                                'voice_over_audio_name' => $slide->voice_over_audio_name,
                                'voice_over_audio_type' => $slide->voice_over_audio_type ?? null,
                                'voice_over_audio_size' => $slide->voice_over_audio_size ?? null,
                            ];
                        })
                        ->values(),
                ];
            })
            ->filter(function ($chapter) {
                return count($chapter['slides']) > 0;
            })
            ->values();

        return [
            'id' => $enrollment->id,
            'hashid' => $enrollment->hashid,
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'company_id' => $enrollment->company_id ?? null,
            'company_request_id' => $enrollment->company_request_id ?? null,
            'individual_request_course_id' => $enrollment->individual_request_course_id ?? null,

            'status' => $enrollment->status,
            'progress_percent' => (float) $enrollment->progress_percent,
            'started_at' => $enrollment->started_at,
            'completed_at' => $enrollment->completed_at,
            'expired_at' => $enrollment->expired_at,
            'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                ? (int) $enrollment->duree_validite_formation
                : null,
            'expiration' => $this->getEnrollmentExpirationState($enrollment),
            'is_expired' => $this->getEnrollmentExpirationState($enrollment)['is_expired'],
            'current_chapter_id' => $enrollment->current_chapter_id,
            'current_slide_id' => $currentSlideId,
            'last_slide_id' => $currentSlideId,
            'access_type' => $enrollment->access_type,
            'is_approved' => (bool) $enrollment->is_approved,
            'attempts_rest' => $enrollment->attempts_rest,
            'comment' => $enrollment->comment,
            'training_request' => $this->formatTrainingRequest($enrollment),

            'slideProgress' => $slideProgress,
            'slide_progress' => $slideProgress,

            'chapterProgress' => $chapterProgress,
            'chapter_progress' => $chapterProgress,

            'course' => $course ? [
                'id' => $course->id,
                'hashid' => $course->hashid ?? Hashids::encode($course->id),
                'category_id' => $course->category_id,
                'title' => $course->title,
                'description' => $course->description,
                'ref' => $course->ref,
                'method' => $course->method,
                'duration_hours' => $course->duration_hours !== null
                    ? (float) $course->duration_hours
                    : null,
                'passing_score' => $course->passing_score !== null
                    ? (float) $course->passing_score
                    : null,
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,

                'image' => $course->image,
                'image_url' => $course->image
                    ? asset('storage/' . $course->image)
                    : ($course->image_url ?? null),

                'type' => $course->type,
                'training_type' => $course->training_type,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'language' => $course->language,
                'price' => $course->price,
                'currency' => $course->currency,
                'is_published' => (bool) $course->is_published,
                'content_type' => $course->content_type ?? 'normal',

                'support_voice_over' => (bool) $course->support_voice_over,
                'voice_over_auto_play' => (bool) $course->voice_over_auto_play,
                'voice_over_provider' => $course->voice_over_provider ?: 'voicerss',
                'voice_over_lang' => $course->voice_over_lang ?: 'en-us',
                'voice_over_voice' => $course->voice_over_voice ?: 'default',

                'category' => $course->category ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ] : null,

                'chapters' => $chapters,
            ] : null,
        ];
    }
    private function isScormCourse($course): bool
    {
        return strtolower((string) ($course?->content_type ?? 'normal')) === 'scorm';
    }

    private function getScormLaunchUrl($course): ?string
    {
        if (!$course) {
            return null;
        }

        if (!empty($course->scorm_launch_url)) {
            return $course->scorm_launch_url;
        }

        if (empty($course->scorm_extract_path) || empty($course->scorm_entry_file)) {
            return null;
        }

        return asset(
            'storage/' .
            trim($course->scorm_extract_path, '/') . '/' .
            ltrim($course->scorm_entry_file, '/')
        );
    }

    private function formatScormEnrollmentForViewer(Enrollment $enrollment): array
    {
        $enrollment->loadMissing([
            'course.category',
            'companyRequest.accreditations',
            'scormTracking',
            'user:id,first_name,middle_name,last_name,email',
        ]);

        $course = $enrollment->course;
        $launchUrl = $this->getScormLaunchUrl($course);
        $tracking = $enrollment->scormTracking;

        /*
        |--------------------------------------------------------------------------
        | Effective real-time SCORM completion
        |--------------------------------------------------------------------------
        |
        | Some SCORM packages report progress_measure = 1 (100%) before sending
        | completion_status = completed. The LMS rule is that 100% progress is
        | sufficient to unlock the final exam, so the viewer and backend must use
        | the same completion state.
        |
        */
        $effectiveProgress = $this->resolveEnrollmentProgressPercent($enrollment);

        $courseCompleted = (
            (bool) $tracking?->is_completed ||
            $effectiveProgress >= 100
        );

        /*
        |--------------------------------------------------------------------------
        | Force a fresh SCORM launch before the final exam attempt
        |--------------------------------------------------------------------------
        |
        | When only one exam attempt remains after a failed attempt, the SCORM
        | tracking row is deleted and the enrollment progression is reset to 0.
        | The viewer uses this flag to remove the matching browser storage before
        | installing the SCORM API, preventing old suspend data from being reused.
        |
        */
        $forceScormRestart = (
            !$tracking &&
            (int) ($enrollment->attempts_rest ?? 0) === 1 &&
            (float) ($enrollment->progress_percent ?? 0) === 0.0
        );

        return [
            'id' => $enrollment->id,
            'hashid' => $enrollment->hashid,
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'status' => $enrollment->status,
            'progress_percent' => $effectiveProgress,
            'scorm_progress_percent' => $effectiveProgress,
            'started_at' => $enrollment->started_at,
            'completed_at' => $enrollment->completed_at,
            'expired_at' => $enrollment->expired_at,
            'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                ? (int) $enrollment->duree_validite_formation
                : null,
            'expiration' => $this->getEnrollmentExpirationState($enrollment),
            'is_expired' => $this->getEnrollmentExpirationState($enrollment)['is_expired'],
            'attempts_rest' => (int) ($enrollment->attempts_rest ?? 0),
            'access_type' => $enrollment->access_type,
            'is_approved' => (bool) $enrollment->is_approved,
            'exam_access_mode' => 'after_completion',
            'course_completed' => $courseCompleted,
            'can_start_exam' => (
                $courseCompleted &&
                (int) ($enrollment->attempts_rest ?? 0) > 0
            ),
            'force_scorm_restart' => $forceScormRestart,
            'training_request' => $this->formatTrainingRequest($enrollment),

            'learner' => [
                'id' => (string) $enrollment->user_id,
                'name' => $this->getUserDisplayName($enrollment->user),
                'email' => $enrollment->user?->email,
            ],

            'scorm_tracking' => [
                'exists' => (bool) $tracking,
                'is_completed' => $courseCompleted,
                'lesson_status' => $tracking?->lesson_status,
                'completion_status' => $tracking?->completion_status,
                'success_status' => $tracking?->success_status,
                'progress_measure' => $tracking?->progress_measure !== null
                    ? (float) $tracking->progress_measure
                    : null,
                'score_raw' => $tracking?->score_raw !== null
                    ? (float) $tracking->score_raw
                    : null,
                'lesson_location' => $tracking?->lesson_location,
                'suspend_data' => $tracking?->suspend_data,
                'cmi_data' => $tracking?->cmi_data ?? [],
                'completed_at' => $tracking?->completed_at,
                'last_committed_at' => $tracking?->last_committed_at,
            ],

            'course' => $course ? [
                'id' => $course->id,
                'hashid' => $course->hashid ?? Hashids::encode($course->id),
                'category_id' => $course->category_id,
                'title' => $course->title,
                'description' => $course->description,
                'ref' => $course->ref,
                'method' => $course->method,
                'duration_hours' => $course->duration_hours !== null
                    ? (float) $course->duration_hours
                    : null,
                'passing_score' => $course->passing_score !== null
                    ? (float) $course->passing_score
                    : null,
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,
                'image' => $course->image,
                'image_url' => $course->image
                    ? asset('storage/' . $course->image)
                    : ($course->image_url ?? null),
                'type' => $course->type,
                'training_type' => $course->training_type,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'language' => $course->language,
                'content_type' => 'scorm',
                'category' => $course->category ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ] : null,

                'scorm_zip_path' => $course->scorm_zip_path,
                'scorm_extract_path' => $course->scorm_extract_path,
                'scorm_entry_file' => $course->scorm_entry_file,
                'scorm_version' => $course->scorm_version,
                'scorm_identifier' => $course->scorm_identifier,
                'scorm_title' => $course->scorm_title,
                'scorm_launch_url' => $launchUrl,
                'scorm_package_available' => (bool) $launchUrl,
            ] : null,
        ];
    }

    private function clampProgressPercent($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(
            min(100, max(0, (float) $value)),
            2
        );
    }

    private function progressMeasureToPercent($value): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 0.0;
        }

        $measure = (float) $value;

        return $this->clampProgressPercent(
            $measure <= 1 ? $measure * 100 : $measure
        );
    }

    private function normalizeScormCmiData($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function parseScormProgressCandidate($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $this->progressMeasureToPercent($value);
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*%$/', $normalized, $matches)) {
            return $this->clampProgressPercent($matches[1]);
        }

        return null;
    }

    private function findProgressInsideScormData($value, int $depth = 0): ?float
    {
        if (!is_array($value) || $depth > 4) {
            return null;
        }

        foreach ($value as $key => $candidate) {
            if (preg_match('/progress|percent|percentage|completion/i', (string) $key)) {
                $parsed = $this->parseScormProgressCandidate($candidate);

                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        foreach ($value as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $nested = $this->findProgressInsideScormData(
                $candidate,
                $depth + 1
            );

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    private function progressFromScormSuspendData($value): ?float
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return $this->findProgressInsideScormData($decoded);
        }

        if (
            preg_match(
                '/(?:progress|percent|percentage|completion)[^0-9]{0,12}(\d+(?:\.\d+)?)\s*%?/i',
                $value,
                $matches
            )
        ) {
            return $this->clampProgressPercent($matches[1]);
        }

        return null;
    }

    private function progressFromScormLocation($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (
            !preg_match(
                '/(?:^|\D)(\d+)\s*(?:\/|of)\s*(\d+)(?:\D|$)/i',
                (string) $value,
                $matches
            )
        ) {
            return null;
        }

        $current = (float) $matches[1];
        $total = (float) $matches[2];

        if ($total <= 0) {
            return null;
        }

        return $this->clampProgressPercent(
            ($current / $total) * 100
        );
    }

    private function resolveEnrollmentProgressPercent(Enrollment $enrollment): float
    {
        $enrollmentProgress = $this->clampProgressPercent(
            $enrollment->progress_percent
        );

        if (!$this->isScormCourse($enrollment->course)) {
            return $enrollmentProgress;
        }

        $enrollment->loadMissing('scormTracking');

        $tracking = $enrollment->scormTracking;

        if (!$tracking) {
            return $enrollmentProgress;
        }

        $cmiData = $this->normalizeScormCmiData(
            $tracking->cmi_data
        );

        $statuses = collect([
            $tracking->lesson_status,
            $tracking->completion_status,
            $tracking->success_status,
            $cmiData['cmi.core.lesson_status'] ?? null,
            $cmiData['cmi.completion_status'] ?? null,
            $cmiData['cmi.success_status'] ?? null,
        ])->map(function ($status) {
            return strtolower(trim((string) $status));
        });

        if (
            (bool) $tracking->is_completed ||
            $statuses->contains(function ($status) {
                return in_array($status, ['completed', 'passed'], true);
            })
        ) {
            return 100.0;
        }

        $progressCandidates = [
            $this->progressMeasureToPercent($tracking->progress_measure),
            $this->progressMeasureToPercent(
                $cmiData['cmi.progress_measure'] ?? null
            ),
            $this->progressMeasureToPercent(
                $cmiData['cmi.core.progress_measure'] ?? null
            ),
            $this->progressFromScormSuspendData(
                $tracking->suspend_data
                    ?? $cmiData['cmi.suspend_data']
                    ?? null
            ) ?? 0.0,
            $this->progressFromScormLocation(
                $tracking->lesson_location
                    ?? $cmiData['cmi.location']
                    ?? $cmiData['cmi.core.lesson_location']
                    ?? null
            ) ?? 0.0,
            $enrollmentProgress,
        ];

        return max($progressCandidates);
    }

    private function formatEnrollmentListItem(Enrollment $enrollment): array
    {
        $course = $enrollment->course;

        $hasPassedExam = $enrollment->relationLoaded('exams')
            ? $enrollment->exams->isNotEmpty()
            : Exam::where('enrollment_id', $enrollment->id)
                ->where(function ($query) {
                    $query
                        ->where('passed', true)
                        ->orWhere('status', 'passed');
                })
                ->exists();

        $expiration = $this->getEnrollmentExpirationState($enrollment);

        $courseAccessLocked = (
            $expiration['is_expired'] ||
            $hasPassedExam ||
            (int) ($enrollment->attempts_rest ?? 0) <= 0
        );

        $contentType = strtolower((string) ($course?->content_type ?: 'normal'));
        $isScorm = $contentType === 'scorm';
        $tracking = $isScorm ? $enrollment->scormTracking : null;

        $effectiveProgress = $hasPassedExam
            ? 100.0
            : $this->resolveEnrollmentProgressPercent($enrollment);

        $courseCompleted = $hasPassedExam || (
            $isScorm
                ? ((bool) $tracking?->is_completed || $effectiveProgress >= 100)
                : $effectiveProgress >= 100
        );

        $displayStatus = (string) ($enrollment->status ?: 'not_started');

        if ($expiration['is_expired']) {
            $displayStatus = 'expired';
        } elseif ($displayStatus !== 'expired') {
            if ($courseCompleted) {
                $displayStatus = 'completed';
            } elseif ($effectiveProgress > 0) {
                $displayStatus = 'in_progress';
            }
        }

        return [
            'id' => $enrollment->id,
            'hashid' => $enrollment->hashid,
            'course_id' => $enrollment->course_id,
            'status' => $displayStatus,
            'progress_percent' => $effectiveProgress,
            'scorm_progress_percent' => $isScorm
                ? $effectiveProgress
                : null,
            'started_at' => $enrollment->started_at,
            'completed_at' => $enrollment->completed_at,
            'expired_at' => $enrollment->expired_at,
            'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                ? (int) $enrollment->duree_validite_formation
                : null,
            'expiration' => $expiration,
            'is_expired' => $expiration['is_expired'],
            'attempts_rest' => (int) ($enrollment->attempts_rest ?? 0),

            'has_passed_exam' => $hasPassedExam,
            'course_access_locked' => $courseAccessLocked,
            'content_type' => $contentType,
            'is_scorm' => $isScorm,
            'course_completed' => $courseCompleted,
            'exam_access_mode' => 'after_completion',
            'training_request' => $this->formatTrainingRequest($enrollment),

            'scorm_tracking' => $isScorm ? [
                'exists' => (bool) $tracking,
                'is_completed' => (bool) $tracking?->is_completed,
                'lesson_status' => $tracking?->lesson_status,
                'completion_status' => $tracking?->completion_status,
                'success_status' => $tracking?->success_status,
                'progress_percent' => $effectiveProgress,
                'progress_measure' => $tracking?->progress_measure !== null
                    ? (float) $tracking->progress_measure
                    : null,
                'lesson_location' => $tracking?->lesson_location,
                'suspend_data' => $tracking?->suspend_data,
                'cmi_data' => $this->normalizeScormCmiData(
                    $tracking?->cmi_data
                ),
                'last_committed_at' => $tracking?->last_committed_at,
            ] : null,

            'course' => $course ? [
                'id' => $course->id,
                'hashid' => $course->hashid ?? Hashids::encode($course->id),
                'title' => $course->title,
                'description' => $course->description,
                'ref' => $course->ref,
                'method' => $course->method,
                'duration_hours' => $course->duration_hours !== null
                    ? (float) $course->duration_hours
                    : null,
                'passing_score' => $course->passing_score !== null
                    ? (float) $course->passing_score
                    : null,
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,
                'image' => $course->image,
                'image_url' => $course->image
                    ? asset('storage/' . $course->image)
                    : ($course->image_url ?? null),
                'language' => $course->language,
                'type' => $course->type,
                'training_type' => $course->training_type,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'content_type' => $contentType,
                'is_scorm' => $isScorm,
                'scorm_version' => $course->scorm_version,
                'category' => $course->category ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ] : null,
            ] : null,
        ];
    }
    private function getEnrollmentCourseCompletionState(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('course');

        /*
        |--------------------------------------------------------------------------
        | SCORM completion
        |--------------------------------------------------------------------------
        |
        | The final exam is available when the SCORM package explicitly reports
        | completion or when its effective synchronized progress reaches 100%.
        |
        */
        if ($this->isScormCourse($enrollment->course)) {
            $enrollment->loadMissing('scormTracking');

            $tracking = $enrollment->scormTracking;
            $effectiveProgress = $this->resolveEnrollmentProgressPercent($enrollment);

            $isCompleted = (bool) (
                $tracking?->is_completed ||
                $effectiveProgress >= 100
            );

            /*
            |------------------------------------------------------------------
            | Normalize the persisted completion flag
            |------------------------------------------------------------------
            |
            | When a package reaches 100% but delays its explicit completed
            | status, persist is_completed so every later access path uses the
            | same state without requiring the learner to reload the course.
            |
            */
            if (
                $tracking &&
                $isCompleted &&
                !$tracking->is_completed
            ) {
                $tracking->forceFill([
                    'is_completed' => true,
                    'completed_at' => $tracking->completed_at ?: now(),
                ])->save();

                $enrollment->setRelation('scormTracking', $tracking);
            }

            if (
                $isCompleted &&
                (float) ($enrollment->progress_percent ?? 0) < 100
            ) {
                $enrollment->forceFill([
                    'progress_percent' => 100,
                ])->save();
            }

            return [
                'total_slides' => 1,
                'completed_slides' => $isCompleted ? 1 : 0,
                'course_completed' => $isCompleted,
                'completion_mode' => 'scorm_tracking',
                'progress_percent' => $effectiveProgress,
                'lesson_status' => $tracking?->lesson_status,
                'completion_status' => $tracking?->completion_status,
                'success_status' => $tracking?->success_status,
            ];
        }

        $enrollment->loadMissing([
            'course.chapters' => function ($query) {
                $query
                    ->whereHas('slides', function ($slideQuery) {
                        $slideQuery->where('is_active', true);
                    })
                    ->orderBy('sort_order')
                    ->with([
                        'slides' => function ($slideQuery) {
                            $slideQuery
                                ->where('is_active', true)
                                ->orderBy('sort_order');
                        },
                    ]);
            },
        ]);

        $activeSlideIds = ($enrollment->course?->chapters ?? collect())
            ->flatMap(function ($chapter) {
                return $chapter->slides->pluck('id');
            })
            ->unique()
            ->values();

        $totalSlides = $activeSlideIds->count();

        $completedSlides = $totalSlides > 0
            ? SlideProgress::where('enrollment_id', $enrollment->id)
                ->whereIn('slide_id', $activeSlideIds)
                ->where('is_completed', true)
                ->distinct('slide_id')
                ->count('slide_id')
            : 0;

        return [
            'total_slides' => $totalSlides,
            'completed_slides' => $completedSlides,
            'course_completed' => (
                $totalSlides > 0 &&
                $completedSlides >= $totalSlides
            ),
            'completion_mode' => 'slides',
        ];
    }
    private function resetEnrollmentForCourseRetake(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('course');

        if (!$enrollment->course) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Reset a SCORM course before the learner's final exam attempt
        |--------------------------------------------------------------------------
        |
        | Delete all persisted SCORM values, including lesson location, suspend
        | data, score and completion state. The viewer also receives the
        | force_scorm_restart flag so it can remove the browser copy.
        |
        */
        if ($this->isScormCourse($enrollment->course)) {
            $enrollment->scormTracking()->delete();

            $enrollment->update([
                'current_chapter_id' => null,
                'status' => 'in_progress',
                'progress_percent' => 0,
                'completed_at' => null,
                'expired_at' => null,
                'started_at' => now(),
            ]);

            $enrollment->unsetRelation('scormTracking');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Reset a normal slide-based course
        |--------------------------------------------------------------------------
        */
        SlideProgress::where(
            'enrollment_id',
            $enrollment->id
        )->delete();

        ChapterProgress::where(
            'enrollment_id',
            $enrollment->id
        )->delete();

        $firstChapterId = $enrollment->course
            ->chapters()
            ->whereHas('slides', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('sort_order')
            ->value('id');

        $enrollment->update([
            'current_chapter_id' => $firstChapterId,
            'status' => 'in_progress',
            'progress_percent' => 0,
            'completed_at' => null,
            'started_at' => now(),
        ]);

        $enrollment->unsetRelation('slideProgress');
        $enrollment->unsetRelation('chapterProgress');
    }

    private function decodeHashid($hashid): int
    {
        $decoded = Hashids::decode($hashid);

        if (empty($decoded)) {
            abort(404);
        }

        return (int) $decoded[0];
    }

    public function courseFinish($enrollmentHashid)
    {
        $user = auth()->user();

        $enrollmentId = Enrollment::decodeHashid($enrollmentHashid);

        if (!$enrollmentId) {
            abort(404);
        }

        $enrollment = Enrollment::with([
                'course.category',
                'course.chapters' => function ($query) {
                    $query
                        ->whereHas('slides', function ($slideQuery) {
                            $slideQuery->where('is_active', true);
                        })
                        ->orderBy('sort_order')
                        ->with([
                            'slides' => function ($slideQuery) {
                                $slideQuery
                                    ->where('is_active', true)
                                    ->orderBy('sort_order');
                            },
                        ]);
                },
                'slideProgress',
                'chapterProgress',
                'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
                'certificates.accreditation',
                'exams' => function ($query) {
                    $query->latest();
                },
            ])
            ->where('id', $enrollmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $course = $enrollment->course;

        $completion = $this->getEnrollmentCourseCompletionState($enrollment);

        $attemptsRest = (int) ($enrollment->attempts_rest ?? 0);

        $passedExam = $enrollment->exams
            ->first(function ($exam) {
                return (bool) $exam->passed || $exam->status === 'passed';
            });

        $hasPassedExam = (bool) $passedExam;

        /*
        |--------------------------------------------------------------------------
        | Certificate visibility for learner
        |--------------------------------------------------------------------------
        |
        | Individual enrollments always return true through the Enrollment helper.
        | Company enrollments respect certificate_visible_to_learner.
        |
        */
        $canAccessCertificates = $enrollment->canLearnerAccessCertificate();

        if ($passedExam) {
            $expirationReference = $passedExam->submitted_at
                ?? $passedExam->completed_at
                ?? $enrollment->completed_at
                ?? now();

            $this->synchronizeEnrollmentExpiration(
                $enrollment,
                $expirationReference
            );

            $enrollment->refresh();
        }

        $expiration = $this->getEnrollmentExpirationState($enrollment);

        /*
        |--------------------------------------------------------------------------
        | Accreditation-specific certificates
        |--------------------------------------------------------------------------
        |
        | A request can contain EASA, DAC Morocco, or both. A passed exam therefore
        | creates one certificate per selected accreditation.
        |
        */
        $certificates = collect();

        if ($hasPassedExam) {
            /*
             * Certificates are still generated even when learner diffusion is
             * disabled because the company/admin may still need them.
             * Only the learner-facing payload is hidden.
             */
            $generatedCertificates = $this->createCertificatesForPassedExam(
                $passedExam
            );

            if ($canAccessCertificates) {
                $certificates = $generatedCertificates;
            }
        }

        $inProgressExam = $hasPassedExam
            ? null
            : $enrollment->exams->first(function ($exam) {
                return $exam->status === 'in_progress';
            });

        $hasInProgressExam = (bool) $inProgressExam;
        $trainingAccess = $this->getTrainingAccessState($enrollment);

        $canViewCourse = (
            !$hasPassedExam &&
            $attemptsRest > 0 &&
            $trainingAccess['can_access_course']
        );

        /*
        |--------------------------------------------------------------------------
        | Exam access
        |--------------------------------------------------------------------------
        |
        | A current in-progress attempt can still be resumed, even if its start
        | already decremented attempts_rest to zero. A new attempt is never
        | allowed after a passed exam or when no attempts remain.
        |
        */
        $canStartExam = (
            !$hasPassedExam &&
            (
                $hasInProgressExam ||
                (
                    $attemptsRest > 0 &&
                    $completion['course_completed'] &&
                    $trainingAccess['can_start_exam']
                )
            )
        );

        $examBlockedReason = null;

        if ($hasPassedExam) {
            $examBlockedReason = __('The final exam has already been passed.');
        } elseif ($hasInProgressExam) {
            $examBlockedReason = null;
        } elseif ($attemptsRest <= 0) {
            $examBlockedReason = __('No exam attempts remaining.');
        } elseif (!$trainingAccess['can_start_exam']) {
            $examBlockedReason = $trainingAccess['blocked_reason'];
        } elseif (!$completion['course_completed']) {
            $examBlockedReason = __('You must complete the entire course before starting the exam.');
        }

        $exams = $enrollment->exams
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'hashid' => Hashids::encode($exam->id),
                    'status' => $exam->status ?? '-',
                    'score' => $exam->score ?? null,
                    'passed' => (bool) $exam->passed,
                    'total_questions' => $exam->total_questions ?? null,
                    'correct_answers' => $exam->correct_answers ?? null,
                    'attempt_number' => $exam->attempt_number ?? null,
                    'started_at' => $exam->started_at ?? null,
                    'submitted_at' => $exam->submitted_at ?? null,
                    'completed_at' => $exam->completed_at ?? null,
                    'created_at' => $exam->created_at,
                ];
            })
            ->values();

        return Inertia::render('learner/espace/courseFinish', [
            'enrollment' => [
                'id' => $enrollment->id,
                'hashid' => $enrollment->hashid,
                'status' => $enrollment->status,
                'progress_percent' => (float) $enrollment->progress_percent,
                'started_at' => $enrollment->started_at,
                'completed_at' => $enrollment->completed_at,
                'expired_at' => $enrollment->expired_at,
                'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                    ? (int) $enrollment->duree_validite_formation
                    : null,
                'expiration' => $expiration,
                'is_expired' => $expiration['is_expired'],
                'attempts_rest' => $attemptsRest,
                'access_type' => $enrollment->access_type,
                'is_approved' => (bool) $enrollment->is_approved,

                'total_slides' => $completion['total_slides'],
                'completed_slides' => $completion['completed_slides'],
                'course_completed' => $completion['course_completed'],
                'completion_mode' => $completion['completion_mode'] ?? 'slides',
                'is_scorm' => $this->isScormCourse($course),

                'has_passed_exam' => $hasPassedExam,
                'passed_exam_hashid' => $passedExam
                    ? Hashids::encode($passedExam->id)
                    : null,

                'training_request' => $this->formatTrainingRequest($enrollment),

                'certificate_visible_to_learner' => (bool)
                    $enrollment->certificate_visible_to_learner,
                'can_access_certificates' => $canAccessCertificates,

                'certificate' => $certificates->first() ? [
                    'id' => $certificates->first()->id,
                    'hashid' => Hashids::encode($certificates->first()->id),
                    'name' => $certificates->first()->name,
                    'certificate_number' => $certificates->first()->certificate_number,
                    'issued_at' => $certificates->first()->issued_at,
                    'expired_at' => $enrollment->expired_at,
                    'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                        ? (int) $enrollment->duree_validite_formation
                        : null,
                ] : null,

                'certificates' => $certificates
                    ->map(function ($certificate) use ($enrollment) {
                        return [
                            'id' => $certificate->id,
                            'hashid' => Hashids::encode($certificate->id),
                            'name' => $certificate->name,
                            'certificate_number' => $certificate->certificate_number,
                            'issuer_name' => $certificate->issuer_name,
                            'approval_reference' => $certificate->approval_reference,
                            'issued_at' => $certificate->issued_at,
                            'expired_at' => $enrollment->expired_at,
                            'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                                ? (int) $enrollment->duree_validite_formation
                                : null,
                            'accreditation' => $certificate->accreditation ? [
                                'id' => $certificate->accreditation->id,
                                'code' => strtolower((string) $certificate->accreditation->code),
                                'name' => $certificate->accreditation->name,
                                'organization_name' => $certificate->accreditation->organization_name,
                                'authority_name' => $certificate->accreditation->authority_name,
                                'approval_reference' => $certificate->accreditation->approval_reference,
                            ] : null,
                        ];
                    })
                    ->values(),

                'has_in_progress_exam' => $hasInProgressExam,
                'in_progress_exam_hashid' => $inProgressExam
                    ? Hashids::encode($inProgressExam->id)
                    : null,

                'can_view_course' => $canViewCourse,
                'can_start_exam' => $canStartExam,
                'exam_blocked_reason' => $examBlockedReason,

                'course' => $course ? [
                    'id' => $course->id,
                    'hashid' => $course->hashid,
                    'title' => $course->title,
                    'description' => $course->description,
                    'ref' => $course->ref,
                    'method' => $course->method,
                    'duration_hours' => $course->duration_hours !== null
                        ? (float) $course->duration_hours
                        : null,
                    'passing_score' => $course->passing_score !== null
                        ? (float) $course->passing_score
                        : null,
                    'syllabus' => $course->syllabus,
                    'syllabus_url' => $course->syllabus
                        ? asset('storage/' . $course->syllabus)
                        : null,
                    'syllabus_name' => $course->syllabus
                        ? basename($course->syllabus)
                        : null,
                    'image' => $course->image,
                    'image_url' => $course->image
                        ? asset('storage/' . $course->image)
                        : ($course->image_url ?? null),
                    'language' => $course->language,
                    'training_type' => $course->training_type,
                    'skill_level' => $course->skill_level,
                    'course_focus' => $course->course_focus,
                    'content_type' => $course->content_type ?? 'normal',
                    'scorm_version' => $course->scorm_version,
                    'category' => $course->category ? [
                        'id' => $course->category->id,
                        'name' => $course->category->name,
                    ] : null,
                ] : null,

                'exams' => $exams,
            ],
        ]);
    }
    public function downloadCertificate(Request $request, $enrollmentHashid)
    {
        $user = auth()->user();

        $enrollmentId = Enrollment::decodeHashid($enrollmentHashid);

        if (!$enrollmentId) {
            abort(404, 'Enrollment not found.');
        }

        $enrollment = Enrollment::with([
                'user',
                'course.category',
                'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
                'certificates.accreditation',
                'exams' => function ($query) {
                    $query
                        ->where(function ($examQuery) {
                            $examQuery
                                ->where('passed', true)
                                ->orWhere('status', 'passed');
                        })
                        ->latest('submitted_at')
                        ->latest('id');
                },
            ])
            ->where('id', $enrollmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Certificate diffusion permission
        |--------------------------------------------------------------------------
        |
        | Hiding the button in Vue is not a security boundary. A learner without
        | permission must also be blocked when calling the download URL directly.
        |
        */
        if (!$enrollment->canLearnerAccessCertificate()) {
            abort(
                403,
                'Certificate access is not available for this enrollment.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Passed exam
        |--------------------------------------------------------------------------
        */

        $passedExam = $enrollment->exams->first();

        if (!$passedExam) {
            abort(
                403,
                'No passed exam was found for this enrollment.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Certificates linked to the passed exam
        |--------------------------------------------------------------------------
        */

        $linkedCertificates = $this->createCertificatesForPassedExam(
            $passedExam
        );

        $certificateHashid = $request->query('certificate');

        $certificate = null;

        if ($certificateHashid) {

            $decodedCertificate = Hashids::decode(
                $certificateHashid
            );

            $certificateId = $decodedCertificate[0] ?? null;

            if ($certificateId) {
                $certificate = $linkedCertificates
                    ->firstWhere(
                        'id',
                        (int) $certificateId
                    );
            }

        } elseif ($linkedCertificates->count() === 1) {

            $certificate = $linkedCertificates->first();
        }

        if (!$certificate) {
            abort(
                404,
                'The selected certificate was not found for this enrollment.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Main data
        |--------------------------------------------------------------------------
        */

        $learner = $enrollment->user;
        $course = $enrollment->course;
        $accreditation = $certificate->accreditation;

        if (!$learner || !$course || !$accreditation) {
            abort(
                500,
                'Certificate data is incomplete.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Learner display name
        |--------------------------------------------------------------------------
        */

        $learnerDisplayName = $this->getUserDisplayName(
            $learner
        );

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        |
        | Les nouveaux templates doivent utiliser :
        |
        | $learner->first_name
        | $learner->middle_name
        | $learner->last_name
        |
        | Mais on conserve temporairement "name" pour les anciens templates.
        |
        */

        $learner->setAttribute(
            'name',
            $learnerDisplayName
        );

        /*
        |--------------------------------------------------------------------------
        | Certificate dates
        |--------------------------------------------------------------------------
        */

        $completedAt = $certificate->issued_at
            ?? $passedExam->submitted_at
            ?? $passedExam->completed_at
            ?? $enrollment->completed_at
            ?? now();

        $completedAtCarbon = Carbon::parse(
            $completedAt
        );

        $completedAtFormatted = $completedAtCarbon
            ->format('d-m-Y');

        $completedTimeFormatted = $completedAtCarbon
            ->format('H:i');

        $expiredAtFormatted = $enrollment->expired_at
            ? Carbon::parse(
                $enrollment->expired_at
            )->format('d-m-Y')
            : null;

        /*
        |--------------------------------------------------------------------------
        | Certificate number
        |--------------------------------------------------------------------------
        */

        $certificateNumber = $certificate
            ->certificate_number;

        /*
        |--------------------------------------------------------------------------
        | Certificate template
        |--------------------------------------------------------------------------
        */

        $template = $this->resolveCertificateTemplate(
            $accreditation
        );

        /*
        |--------------------------------------------------------------------------
        | Certificate assets
        |--------------------------------------------------------------------------
        |
        | DAC :
        | - logo DAC/HALIAIR
        | - background DAC
        | - page 2 DAC
        |
        | EASA :
        | - logo EASA/HALIAIR
        | - background EASA
        | - page 2 EASA
        |
        */

        $assets = $this->resolveCertificateAssets(
            $accreditation
        );

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
                $template,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Assets
                    |--------------------------------------------------------------------------
                    */

                    'certificateLogo' => $assets['logo'],

                    'imageBG' => $assets['background'],

                    'SECONDEpage' => $assets['second_page'],

                    /*
                    |--------------------------------------------------------------------------
                    | Certificate
                    |--------------------------------------------------------------------------
                    */

                    'certificate' => $certificate,

                    'certificateNumber' => $certificateNumber,

                    /*
                    |--------------------------------------------------------------------------
                    | Accreditation
                    |--------------------------------------------------------------------------
                    */

                    'accreditation' => $accreditation,

                    /*
                    |--------------------------------------------------------------------------
                    | Training
                    |--------------------------------------------------------------------------
                    */

                    'trainingRequest' => $enrollment->companyRequest,

                    'enrollment' => $enrollment,

                    /*
                    |--------------------------------------------------------------------------
                    | Learner
                    |--------------------------------------------------------------------------
                    */

                    'learner' => $learner,

                    'learnerDisplayName' => $learnerDisplayName,

                    /*
                    |--------------------------------------------------------------------------
                    | Course
                    |--------------------------------------------------------------------------
                    */

                    'course' => $course,
                   
                    /*
                    |--------------------------------------------------------------------------
                    | Exam
                    |--------------------------------------------------------------------------
                    */

                    'exam' => $passedExam,

                    /*
                    |--------------------------------------------------------------------------
                    | Dates
                    |--------------------------------------------------------------------------
                    */

                    'completedAt' => $completedAtFormatted,

                    'completedTime' => $completedTimeFormatted,

                    'expiredAt' => $expiredAtFormatted,

                    'validUntil' => $expiredAtFormatted,

                    /*
                    |--------------------------------------------------------------------------
                    | Validity
                    |--------------------------------------------------------------------------
                    */

                    'validityMonths' =>
                        $enrollment->duree_validite_formation !== null
                            ? (int) $enrollment->duree_validite_formation
                            : null,

                    'hasExpiration' =>
                        $expiredAtFormatted !== null,
                ]
            )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->setOptions([
                'enable_php' => true,
                'isRemoteEnabled' => true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | PDF filename
        |--------------------------------------------------------------------------
        */

        $learnerName = Str::slug(
            $learnerDisplayName ?: 'learner'
        );

        $courseName = Str::slug(
            $course->title ?? 'training-course'
        );


        $accreditationCode = Str::slug(
            $accreditation->code ?? 'certificate'
        );

        $fileName = sprintf(
            'certificate-%s-%s-%s.pdf',
            $accreditationCode,
            $courseName,
            $learnerName
        );

        return $pdf->stream(
            $fileName
        );
    }

    public function startExam($enrollmentHashid)
    {
        $user = auth()->user();

        $enrollmentId = Enrollment::decodeHashid($enrollmentHashid);

        if (!$enrollmentId) {
            abort(404);
        }

        try {
            $exam = DB::transaction(function () use ($user, $enrollmentId) {
                $enrollment = Enrollment::with([
                        'course.plan.details',
                        'companyRequest.accreditations',
                'individualRequestCourse.individualRequest',
                        'course.chapters' => function ($query) {
                            $query
                                ->whereHas('slides', function ($slideQuery) {
                                    $slideQuery->where('is_active', true);
                                })
                                ->orderBy('sort_order')
                                ->with([
                                    'slides' => function ($slideQuery) {
                                        $slideQuery
                                            ->where('is_active', true)
                                            ->orderBy('sort_order');
                                    },
                                ]);
                        },
                    ])
                    ->where('id', $enrollmentId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $hasPassedExam = Exam::where('enrollment_id', $enrollment->id)
                    ->where(function ($query) {
                        $query
                            ->where('passed', true)
                            ->orWhere('status', 'passed');
                    })
                    ->exists();

                if ($hasPassedExam) {
                    throw ValidationException::withMessages([
                        'exam' => __('The final exam has already been passed.'),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Resume the current attempt
                |--------------------------------------------------------------------------
                |
                | The original expiration date is preserved. Reloading the page
                | never restarts or extends the timer.
                |
                */
                $existingExam = Exam::where('enrollment_id', $enrollment->id)
                    ->where('status', 'in_progress')
                    ->latest()
                    ->first();

                if ($existingExam) {
                    $this->ensureExamTiming($existingExam);

                    return $existingExam;
                }

                $trainingAccess = $this->getTrainingAccessState($enrollment);

                if (!$trainingAccess['can_start_exam']) {
                    throw ValidationException::withMessages([
                        'training_period' => $trainingAccess['blocked_reason'],
                    ]);
                }

                if ((int) $enrollment->attempts_rest <= 0) {
                    throw ValidationException::withMessages([
                        'attempts_rest' => __('No exam attempts remaining.'),
                    ]);
                }

                if (!$enrollment->course) {
                    throw ValidationException::withMessages([
                        'course' => __('Course not found.'),
                    ]);
                }

                $completion = $this->getEnrollmentCourseCompletionState($enrollment);

                if (!$completion['course_completed']) {
                    throw ValidationException::withMessages([
                        'course_progress' => __('You must complete the entire course before starting the exam.'),
                    ]);
                }

                if (
                    !$enrollment->course->plan ||
                    !$enrollment->course->plan->is_active
                ) {
                    throw ValidationException::withMessages([
                        'plan' => __('No active exam plan found for this course.'),
                    ]);
                }

                $questions = $this->generateExamQuestionsFromPlan($enrollment);

                if ($questions->isEmpty()) {
                    throw ValidationException::withMessages([
                        'questions' => __('No questions available to generate exam.'),
                    ]);
                }

                $lastAttemptNumber = Exam::where('enrollment_id', $enrollment->id)
                    ->max('attempt_number');

                $attemptNumber = ((int) $lastAttemptNumber) + 1;
                $timeLimitMinutes = $this->calculateExamTimeLimitMinutes(
                    $questions->count()
                );

                $startedAt = now();
                $expiresAt = $startedAt
                    ->copy()
                    ->addMinutes($timeLimitMinutes);

                $exam = Exam::create([
                    'enrollment_id' => $enrollment->id,
                    'title' => 'Final Exam - ' . $enrollment->course->title,
                    'description' => '',
                    'pass_score' => $this->resolveCoursePassingScore(
                        $enrollment->course
                    ),
                    'max_attempts' => $attemptNumber +
                        ((int) $enrollment->attempts_rest) - 1,
                    'time_limit_minutes' => $timeLimitMinutes,
                    'expires_at' => $expiresAt,
                    'is_final_exam' => true,
                    'attempt_number' => $attemptNumber,
                    'status' => 'in_progress',
                    'submission_type' => null,
                    'score' => null,
                    'passed' => false,
                    'total_questions' => $questions->count(),
                    'correct_answers' => 0,
                    'started_at' => $startedAt,
                ]);

                $attachData = [];

                foreach ($questions as $question) {
                    $attachData[$question->id] = [
                        'response_user' => null,
                        'is_correct' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $exam->questions()->attach($attachData);

                /*
                |--------------------------------------------------------------------------
                | The attempt is consumed as soon as the timed exam starts
                |--------------------------------------------------------------------------
                */
                $enrollment->decrement('attempts_rest');

                return $exam;
            });

            return redirect()
                ->route(
                    'learner-space.exam.show',
                    Hashids::encode($exam->id)
                );
        } catch (ValidationException $exception) {
            return redirect()
                ->route(
                    'learner-space.course-finish',
                    $enrollmentHashid
                )
                ->with(
                    'error',
                    collect($exception->errors())->flatten()->first()
                );
        }
    }


    private function generateExamQuestionsFromPlan(Enrollment $enrollment)
    {
        $course = $enrollment->course;
        $plan = $course?->plan;

        if (!$course || !$plan) {
            return collect();
        }

        $planDetails = $plan->details()
            ->where(function ($query) {
                $query->where('own_questions', '>', 0)
                    ->orWhere('total_questions', '>', 0);
            })
            ->orderBy('id')
            ->get();

        if ($planDetails->isEmpty()) {
            throw ValidationException::withMessages([
                'plan' => __('The exam plan does not contain question quantities.'),
            ]);
        }

        $selectedQuestions = collect();

        foreach ($planDetails as $detail) {
            $neededQuestions = (int) ($detail->own_questions ?: $detail->total_questions);

            if ($neededQuestions <= 0) {
                continue;
            }

            $questions = Question::query()
                ->whereHas('bank', function ($query) use ($course) {
                    $query->where('course_id', $course->id);
                })
                ->where('name_chapter', $detail->name)
                ->whereNotIn('id', $selectedQuestions->pluck('id')->values())
                ->inRandomOrder()
                ->limit($neededQuestions)
                ->get();

            if ($questions->count() < $neededQuestions) {
                throw ValidationException::withMessages([
                    'questions' => __('Not enough questions in bank for section:') . ' ' . $detail->name,
                ]);
            }

            $selectedQuestions = $selectedQuestions->merge($questions);
        }

        return $selectedQuestions
            ->unique('id')
            ->shuffle()
            ->values();
    }


    public function showExam($examHashid)
    {
        $user = auth()->user();

        $examId = $this->decodeHashid($examHashid);

        $exam = $this->findLearnerExam($examId, $user->id);

        /*
        |--------------------------------------------------------------------------
        | Server-side expiration safeguard
        |--------------------------------------------------------------------------
        |
        | If JavaScript was stopped, the tab was closed, or the learner returns
        | later, the server still submits the expired attempt automatically.
        |
        */
        $this->autoSubmitExamWhenExpired($exam);

        $exam = $this->findLearnerExam($examId, $user->id);

        $completion = $this->getEnrollmentCourseCompletionState(
            $exam->enrollment
        );

        $attemptsRest = (int) (
            $exam->enrollment->attempts_rest ?? 0
        );

        $hasPassedExam = Exam::where(
                'enrollment_id',
                $exam->enrollment_id
            )
            ->where(function ($query) {
                $query
                    ->where('passed', true)
                    ->orWhere('status', 'passed');
            })
            ->exists();

        $requiresCourseRetake = (
            !$hasPassedExam &&
            $exam->status === 'failed' &&
            $attemptsRest === 1 &&
            !$completion['course_completed']
        );

        $canRetryExam = (
            !$hasPassedExam &&
            $exam->status === 'failed' &&
            $attemptsRest > 0 &&
            $completion['course_completed']
        );

        $expiresAt = $exam->expires_at
            ? Carbon::parse($exam->expires_at)
            : null;

        $remainingSeconds = (
            $exam->status === 'in_progress' &&
            $expiresAt
        )
            ? max(0, now()->diffInSeconds($expiresAt, false))
            : 0;

        return Inertia::render('learner/espace/examShow', [
            'exam' => [
                'id' => $exam->id,
                'hashid' => Hashids::encode($exam->id),
                'title' => $exam->title,
                'description' => $exam->description,
                'status' => $exam->status,
                'score' => $exam->score,
                'passed' => (bool) $exam->passed,
                'has_passed_exam' => $hasPassedExam,
                'pass_score' => (float) $exam->pass_score,
                'total_questions' => (int) $exam->total_questions,
                'correct_answers' => (int) $exam->correct_answers,
                'attempt_number' => (int) $exam->attempt_number,
                'started_at' => $exam->started_at,
                'submitted_at' => $exam->submitted_at,
                'duration_seconds' => $exam->duration_seconds,

                'time_limit_minutes' => (int) $exam->time_limit_minutes,
                'expires_at' => $expiresAt?->toIso8601String(),
                'server_now' => now()->toIso8601String(),
                'remaining_seconds' => $remainingSeconds,
                'submission_type' => $exam->submission_type,
                'auto_submitted_at' => $exam->auto_submitted_at,

                'requires_course_retake' => $requiresCourseRetake,
                'can_retry_exam' => $canRetryExam,
                'training_request' => $this->formatTrainingRequest(
                    $exam->enrollment
                ),

                'enrollment' => [
                    'id' => $exam->enrollment->id,
                    'hashid' => $exam->enrollment->hashid,
                    'status' => $exam->enrollment->status,
                    'progress_percent' => (float) $exam
                        ->enrollment
                        ->progress_percent,
                    'course_completed' => $completion['course_completed'],
                    'total_slides' => $completion['total_slides'],
                    'completed_slides' => $completion['completed_slides'],
                    'attempts_rest' => $attemptsRest,
                ],

                'course' => $exam->enrollment->course ? [
                    'id' => $exam->enrollment->course->id,
                    'hashid' => $exam->enrollment->course->hashid,
                    'title' => $exam->enrollment->course->title,
                    'category' => $exam->enrollment->course->category ? [
                        'id' => $exam->enrollment->course->category->id,
                        'name' => $exam->enrollment->course->category->name,
                    ] : null,
                ] : null,

                /*
                 * Questions are sent only while the attempt is still active.
                 * Once submitted, the learner receives only the final score and
                 * exam status. Correct answers and per-question correction are
                 * never exposed to the browser.
                 */
                'questions' => $exam->status === 'in_progress'
                    ? $exam->questions
                        ->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question' => $question->question,
                                'name_chapter' => $question->name_chapter,
                                'response1' => $question->response1,
                                'response2' => $question->response2,
                                'response3' => $question->response3,
                                'response_user' => $question
                                    ->pivot
                                    ->response_user,
                            ];
                        })
                        ->values()
                    : collect(),
            ],
        ]);
    }

    public function submitExam(Request $request, $examHashid)
    {
        $user = auth()->user();

        $examId = $this->decodeHashid($examHashid);

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string'],
            'auto_submit' => ['nullable', 'boolean'],
        ]);

        $exam = Exam::with([
                'enrollment.course',
                'questions',
            ])
            ->whereHas('enrollment', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($examId);

        if ($exam->status !== 'in_progress') {
            return redirect()
                ->route(
                    'learner-space.exam.show',
                    Hashids::encode($exam->id)
                )
                ->with(
                    'error',
                    'This exam has already been submitted.'
                );
        }

        $this->ensureExamTiming($exam);

        $isExpired = $this->isExamExpired($exam);

        $autoSubmitted = (
            (bool) ($validated['auto_submit'] ?? false) ||
            $isExpired
        );

        $answers = $validated['answers'] ?? [];

        if (!$autoSubmitted) {
            $this->validateManualExamCompletion(
                $exam,
                $answers
            );
        }

        $result = $this->finalizeExamAttempt(
            $exam,
            $answers,
            $autoSubmitted
        );

        $passed = $result['passed'];
        $remainingAttempts = $result['remaining_attempts'];

        if ($autoSubmitted) {
            $message = $passed
                ? 'Time expired. The exam was submitted automatically and passed.'
                : 'Time expired. The exam was submitted automatically.';
        } elseif ($passed) {
            $message = 'Congratulations! You passed the final exam.';
        } elseif ($remainingAttempts > 1) {
            $message = 'Exam failed. You can use another attempt without repeating the course.';
        } elseif ($remainingAttempts === 1) {
            $message = 'Exam failed. You must complete the entire course again before using your final attempt.';
        } else {
            $message = 'Exam failed. No attempts remaining.';
        }

        return redirect()
            ->route(
                'learner-space.exam.show',
                Hashids::encode($exam->id)
            )
            ->with(
                $passed ? 'success' : 'error',
                $message
            );
    }



    public function saveExamAnswer(Request $request, $examHashid)
    {
        $user = auth()->user();
        $examId = $this->decodeHashid($examHashid);

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer' => [
                'nullable',
                'string',
                'in:response1,response2,response3',
            ],
        ]);

        $result = DB::transaction(function () use (
            $user,
            $examId,
            $validated
        ) {
            $exam = Exam::with([
                    'enrollment.course',
                    'questions',
                ])
                ->whereHas('enrollment', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->lockForUpdate()
                ->findOrFail($examId);

            if ($exam->status !== 'in_progress') {
                return [
                    'saved' => false,
                    'expired' => true,
                    'message' => __('This exam is already closed.'),
                ];
            }

            $this->ensureExamTiming($exam);

            if ($this->isExamExpired($exam)) {
                $this->finalizeExamAttempt($exam, [], true);

                return [
                    'saved' => false,
                    'expired' => true,
                    'message' => __('Exam time has expired.'),
                ];
            }

            $question = $exam->questions->firstWhere(
                'id',
                (int) $validated['question_id']
            );

            if (!$question) {
                abort(403, 'This question does not belong to the exam.');
            }

            $answer = $validated['answer'] ?? null;

            $answerText = $this->normalizeExamAnswer(
                $question,
                $answer
            );

            $exam->questions()->updateExistingPivot(
                $question->id,
                [
                    'response_user' => $answerText,
                    'is_correct' => false,
                    'updated_at' => now(),
                ]
            );

            return [
                'saved' => true,
                'expired' => false,
                'remaining_seconds' => $this
                    ->getExamRemainingSeconds($exam),
            ];
        });

        return response()->json(
            $result,
            $result['expired'] ? 409 : 200
        );
    }

    private function findLearnerExam(
        int $examId,
        int $userId
    ): Exam {
        return Exam::with([
                'enrollment.course.category',
                'enrollment.companyRequest.accreditations',
                'enrollment.course.chapters' => function ($query) {
                    $query
                        ->whereHas('slides', function ($slideQuery) {
                            $slideQuery->where('is_active', true);
                        })
                        ->orderBy('sort_order')
                        ->with([
                            'slides' => function ($slideQuery) {
                                $slideQuery
                                    ->where('is_active', true)
                                    ->orderBy('sort_order');
                            },
                        ]);
                },
                'questions' => function ($query) {
                    $query->orderBy('question_exam.id');
                },
            ])
            ->whereHas('enrollment', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->findOrFail($examId);
    }

    private function resolveCoursePassingScore($course): float
    {
        $passingScore = (float) ($course?->passing_score ?? 80);

        return min(100, max(0, $passingScore));
    }

    private function calculateExamTimeLimitMinutes(
        int $totalQuestions
    ): int {
        $calculatedMinutes = (int) ceil(
            $totalQuestions * 1.5
        );

        return max(20, $calculatedMinutes);
    }

    private function ensureExamTiming(Exam $exam): void
    {
        $updates = [];

        $timeLimitMinutes = (int) (
            $exam->time_limit_minutes ?? 0
        );

        if ($timeLimitMinutes <= 0) {
            $timeLimitMinutes = $this
                ->calculateExamTimeLimitMinutes(
                    (int) $exam->total_questions
                );

            $updates['time_limit_minutes'] = $timeLimitMinutes;
        }

        $startedAt = $exam->started_at
            ? Carbon::parse($exam->started_at)
            : now();

        if (!$exam->started_at) {
            $updates['started_at'] = $startedAt;
        }

        if (!$exam->expires_at) {
            $updates['expires_at'] = $startedAt
                ->copy()
                ->addMinutes($timeLimitMinutes);
        }

        if (!empty($updates)) {
            $exam->update($updates);
            $exam->refresh();
        }
    }

    private function getExamRemainingSeconds(Exam $exam): int
    {
        if (
            $exam->status !== 'in_progress' ||
            !$exam->expires_at
        ) {
            return 0;
        }

        return max(
            0,
            now()->diffInSeconds(
                Carbon::parse($exam->expires_at),
                false
            )
        );
    }

    private function isExamExpired(Exam $exam): bool
    {
        if (
            $exam->status !== 'in_progress' ||
            !$exam->expires_at
        ) {
            return false;
        }

        return now()->greaterThanOrEqualTo(
            Carbon::parse($exam->expires_at)
        );
    }

    private function autoSubmitExamWhenExpired(Exam $exam): void
    {
        if ($exam->status !== 'in_progress') {
            return;
        }

        $this->ensureExamTiming($exam);

        if (!$this->isExamExpired($exam)) {
            return;
        }

        $this->finalizeExamAttempt(
            $exam,
            [],
            true
        );
    }

    private function validateManualExamCompletion(
        Exam $exam,
        array $answers
    ): void {
        $exam->loadMissing('questions');

        $missingQuestionIds = $exam->questions
            ->filter(function ($question) use ($answers) {
                $requestAnswer = $answers[$question->id] ?? null;
                $savedAnswer = $question->pivot->response_user ?? null;

                return empty($requestAnswer) && empty($savedAnswer);
            })
            ->pluck('id')
            ->values();

        if ($missingQuestionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => __('Answer every question before submitting the exam.'),
            ]);
        }
    }

    private function finalizeExamAttempt(
        Exam $exam,
        array $answers,
        bool $autoSubmitted
    ): array {
        $result = DB::transaction(function () use (
            $exam,
            $answers,
            $autoSubmitted
        ) {
            $lockedExam = Exam::with([
                    'enrollment.course',
                    'questions',
                ])
                ->lockForUpdate()
                ->findOrFail($exam->id);

            /*
            |--------------------------------------------------------------------------
            | Already finalized
            |--------------------------------------------------------------------------
            |
            | Do not resend certificate emails if this method is called again for an
            | exam that has already been submitted.
            |
            */
            if ($lockedExam->status !== 'in_progress') {
                return [
                    'passed' => (bool) $lockedExam->passed,
                    'remaining_attempts' => (int) (
                        $lockedExam->enrollment->attempts_rest ?? 0
                    ),
                    'exam_id' => (int) $lockedExam->id,
                    'certificate_ids' => [],
                    'was_finalized' => false,
                ];
            }

            $this->ensureExamTiming($lockedExam);

            $correctAnswers = 0;
            $totalQuestions = $lockedExam->questions->count();

            foreach ($lockedExam->questions as $question) {
                $hasRequestAnswer = array_key_exists(
                    $question->id,
                    $answers
                );

                $rawAnswer = $hasRequestAnswer
                    ? $answers[$question->id]
                    : ($question->pivot->response_user ?? null);

                $answerText = $this->normalizeExamAnswer(
                    $question,
                    $rawAnswer
                );

                $isCorrect = $this->isExamAnswerCorrect(
                    $question,
                    $rawAnswer,
                    $answerText
                );

                if ($isCorrect) {
                    $correctAnswers++;
                }

                $lockedExam->questions()->updateExistingPivot(
                    $question->id,
                    [
                        'response_user' => $answerText,
                        'is_correct' => $isCorrect,
                        'updated_at' => now(),
                    ]
                );
            }

            $score = $totalQuestions > 0
                ? round(
                    ($correctAnswers / $totalQuestions) * 100,
                    2
                )
                : 0;

            $passed = $score >= (float) $lockedExam->pass_score;
            $submittedAt = now();

            $startedAt = $lockedExam->started_at
                ? Carbon::parse($lockedExam->started_at)
                : $submittedAt->copy();

            $durationSeconds = $startedAt->diffInSeconds(
                $submittedAt
            );

            if (
                $autoSubmitted &&
                (int) $lockedExam->time_limit_minutes > 0
            ) {
                $durationSeconds = min(
                    $durationSeconds,
                    (int) $lockedExam->time_limit_minutes * 60
                );
            }

            $lockedExam->update([
                'status' => $passed ? 'passed' : 'failed',
                'score' => $score,
                'passed' => $passed,
                'correct_answers' => $correctAnswers,
                'submitted_at' => $submittedAt,
                'auto_submitted_at' => $autoSubmitted
                    ? $submittedAt
                    : null,
                'submission_type' => $autoSubmitted
                    ? 'timeout'
                    : 'manual',
                'duration_seconds' => $durationSeconds,
            ]);

            $lockedExam->enrollment->refresh();

            $certificateIds = [];

            if ($passed) {
                $expirationDate = $this->calculateEnrollmentExpirationDate(
                    $lockedExam->enrollment,
                    $submittedAt
                );

                $lockedExam->enrollment->update([
                    'status' => 'completed',
                    'completed_at' => (
                        $lockedExam->enrollment->completed_at ??
                        $submittedAt
                    ),
                    'expired_at' => $expirationDate,
                    'progress_percent' => 100,
                ]);

                $certificates = $this->createCertificatesForPassedExam(
                    $lockedExam
                );

                $certificateIds = $certificates
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
            }

            $remainingAttempts = (int) (
                $lockedExam->enrollment->attempts_rest ?? 0
            );

            /*
            |--------------------------------------------------------------------------
            | Require a full course retake before the final exam attempt
            |--------------------------------------------------------------------------
            */
            if (
                !$passed &&
                $remainingAttempts === 1
            ) {
                $this->resetEnrollmentForCourseRetake(
                    $lockedExam->enrollment
                );
            }

            return [
                'passed' => $passed,
                'remaining_attempts' => $remainingAttempts,
                'exam_id' => (int) $lockedExam->id,
                'certificate_ids' => $certificateIds,
                'was_finalized' => true,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Certificate emails after database commit
        |--------------------------------------------------------------------------
        |
        | SMTP failures must never roll back a passed exam or generated certificate.
        |
        */
        if (
            ($result['passed'] ?? false) &&
            ($result['was_finalized'] ?? false)
        ) {
            $this->sendPassedExamCertificateEmails(
                (int) $result['exam_id'],
                $result['certificate_ids'] ?? []
            );
        }

        return $result;
    }


    private function createCertificatesForPassedExam(Exam $exam): Collection
    {
        $exam->loadMissing([
            'enrollment.user',
            'enrollment.course',
            'enrollment.companyRequest.accreditations',
            'enrollment.certificates.accreditation',
        ]);

        if (!(bool) $exam->passed && $exam->status !== 'passed') {
            throw ValidationException::withMessages([
                'certificate' => __('Certificates can be created only for a passed exam.'),
            ]);
        }

        $enrollment = $exam->enrollment;

        if (!$enrollment) {
            throw ValidationException::withMessages([
                'certificate' => __('Enrollment not found for this exam.'),
            ]);
        }

        $companyRequest = $enrollment->companyRequest;

        /*
        |--------------------------------------------------------------------------
        | The company request is the source of truth
        |--------------------------------------------------------------------------
        |
        | No generic certificate is generated. Each certificate must correspond
        | to an accreditation explicitly selected on this training request.
        |
        */
        if (!$companyRequest || $companyRequest->accreditations->isEmpty()) {
            return collect();
        }

        $issuedAt = Carbon::parse(
            $exam->submitted_at
                ?? $exam->completed_at
                ?? $enrollment->completed_at
                ?? now()
        );

        $courseTitle = $enrollment->course?->title ?? 'Training Course';
        $linkedAccreditations = $companyRequest->accreditations
            ->unique('id')
            ->values();

        foreach ($linkedAccreditations as $accreditation) {
            $normalizedCode = strtoupper(
                preg_replace(
                    '/[^A-Za-z0-9]/',
                    '',
                    (string) ($accreditation->code ?: $accreditation->name)
                )
            );

            $certificate = Certificate::withTrashed()->firstOrNew([
                'enrollment_id' => $enrollment->id,
                'accreditation_id' => $accreditation->id,
            ]);

            if ($certificate->trashed()) {
                $certificate->restore();
            }

            $certificateNumber = $certificate->certificate_number
                ?: sprintf(
                    'CERT-%s-%s-%06d',
                    $normalizedCode ?: 'APPROVAL',
                    $issuedAt->format('Y'),
                    $enrollment->id
                );

            $certificate->fill([
                'name' => sprintf(
                    '%s Certificate of Completion - %s',
                    $accreditation->name,
                    $courseTitle
                ),
                'issuer_name' => $accreditation->organization_name,
                'approval_reference' => $accreditation->approval_reference,
                'certificate_number' => $certificateNumber,
                'issued_at' => $issuedAt,
            ]);

            $certificate->save();
        }

        $linkedAccreditationIds = $linkedAccreditations
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return Certificate::with('accreditation')
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('accreditation_id', $linkedAccreditationIds)
            ->orderBy('accreditation_id')
            ->get();
    }


    /**
     * Send the generated certificates to the learner and, when available,
     * to the learner's company email address.
     */
    private function sendPassedExamCertificateEmails(
        int $examId,
        array $certificateIds
    ): void {
        /*
         * Certificate PDF generation plus two SMTP deliveries can take longer
         * than the default 60 seconds on local Windows/Laragon environments.
         */
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Load exam and identify the request type
            |--------------------------------------------------------------------------
            |
            | Individual request:
            | - send one generic completion certificate to the learner only.
            |
            | Company request:
            | - send accreditation certificate(s) to the learner only when the
            |   enrollment allows certificate diffusion;
            | - always send the same certificate(s) to the company email when
            |   available.
            |
            */
            $exam = Exam::with([
                    'enrollment.user.company',
                    'enrollment.course.category',
                    'enrollment.companyRequest.company',
                    'enrollment.companyRequest.accreditations',
                    'enrollment.individualRequestCourse.individualRequest',
                ])
                ->findOrFail($examId);

            $enrollment = $exam->enrollment;
            $learner = $enrollment?->user;

            if (!$enrollment || !$learner) {
                Log::warning(
                    'Passed exam certificate email skipped because learner or enrollment is missing.',
                    [
                        'exam_id' => $examId,
                    ]
                );

                return;
            }

            $isCompanyRequest = (bool) $enrollment->companyRequest;
            $isIndividualRequest = (
                !$isCompanyRequest &&
                (bool) $enrollment->individualRequestCourse
            );

            $mailer = app(MicrosoftOAuthMailer::class);

            /*
            |--------------------------------------------------------------------------
            | Individual request -> learner only
            |--------------------------------------------------------------------------
            |
            | Individual requests do not use the Company Request accreditation list.
            | A generic, non-regulatory Certificate of Completion is generated directly
            | for the email attachment. No email is ever sent to a company here, even
            | if the learner account happens to be linked to a company.
            |
            */
            if ($isIndividualRequest) {
                try {
                    $attachment = $this->buildIndividualCertificateEmailAttachment(
                        $exam
                    );
                } catch (Throwable $exception) {
                    Log::error(
                        'Unable to generate individual certificate PDF attachment.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                            'learner_id' => $learner->id,
                            'error' => $exception->getMessage(),
                        ]
                    );

                    return;
                }

                if (!$learner->email) {
                    Log::warning(
                        'Individual passed exam certificate email skipped because learner email is missing.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                            'learner_id' => $learner->id,
                        ]
                    );

                    return;
                }

                try {
                    $mailer->sendPassedExamCertificatesToLearner(
                        $learner,
                        $enrollment,
                        $exam,
                        [$attachment]
                    );
                } catch (Throwable $exception) {
                    Log::error(
                        'Unable to send individual passed exam certificate to learner.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                            'learner_id' => $learner->id,
                            'learner_email' => $learner->email,
                            'error' => $exception->getMessage(),
                        ]
                    );
                }

                /*
                | Important business rule:
                | Individual Request => learner only. Stop here.
                */
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Company request -> learner + company
            |--------------------------------------------------------------------------
            */
            if ($isCompanyRequest) {
                if (empty($certificateIds)) {
                    Log::warning(
                        'Company passed exam certificate email skipped because no accreditation certificate was generated.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                            'company_request_id' => $enrollment->company_request_id,
                        ]
                    );

                    return;
                }

                $certificates = Certificate::with('accreditation')
                    ->where('enrollment_id', $enrollment->id)
                    ->whereIn('id', $certificateIds)
                    ->orderBy('accreditation_id')
                    ->get();

                if ($certificates->isEmpty()) {
                    Log::warning(
                        'Passed exam certificate email skipped because certificates could not be loaded.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                        ]
                    );

                    return;
                }

                $attachments = $this->buildCertificateEmailAttachments(
                    $exam,
                    $certificates
                );

                if (empty($attachments)) {
                    Log::warning(
                        'Passed exam certificate email skipped because PDF attachments could not be generated.',
                        [
                            'exam_id' => $examId,
                            'enrollment_id' => $enrollment->id,
                        ]
                    );

                    return;
                }

                /*
                | Learner email
                |
                | Company enrollments can disable certificate diffusion. When the
                | flag is false, the learner must not receive the certificate by
                | email either.
                */
                if (
                    $enrollment->canLearnerAccessCertificate() &&
                    $learner->email
                ) {
                    try {
                        $mailer->sendPassedExamCertificatesToLearner(
                            $learner,
                            $enrollment,
                            $exam,
                            $attachments
                        );
                    } catch (Throwable $exception) {
                        Log::error(
                            'Unable to send passed exam certificates to learner.',
                            [
                                'exam_id' => $examId,
                                'enrollment_id' => $enrollment->id,
                                'learner_id' => $learner->id,
                                'learner_email' => $learner->email,
                                'error' => $exception->getMessage(),
                            ]
                        );
                    }
                }

                /*
                | Company email
                |
                | IMPORTANT: only the company linked to this exact Company Request is
                | eligible. There is deliberately no fallback to $learner->company,
                | because an Individual Request must never notify a company.
                */
                $company = $enrollment->companyRequest?->company;
                $companyEmail = $company?->email;

                if (
                    $companyEmail &&
                    strcasecmp(
                        (string) $companyEmail,
                        (string) $learner->email
                    ) !== 0
                ) {
                    try {
                        $mailer->sendPassedExamCertificatesToCompany(
                            $learner,
                            $enrollment,
                            $exam,
                            $companyEmail,
                            $company?->name,
                            $attachments
                        );
                    } catch (Throwable $exception) {
                        Log::error(
                            'Unable to send learner certificates to company.',
                            [
                                'exam_id' => $examId,
                                'enrollment_id' => $enrollment->id,
                                'company_request_id' => $enrollment->company_request_id,
                                'company_id' => $company?->id,
                                'company_email' => $companyEmail,
                                'learner_id' => $learner->id,
                                'error' => $exception->getMessage(),
                            ]
                        );
                    }
                }

                return;
            }

            Log::warning(
                'Passed exam certificate email skipped because the enrollment is not linked to a Company Request or Individual Request.',
                [
                    'exam_id' => $examId,
                    'enrollment_id' => $enrollment->id,
                    'company_request_id' => $enrollment->company_request_id,
                    'individual_request_course_id' => $enrollment->individual_request_course_id,
                ]
            );
        } catch (Throwable $exception) {
            Log::error(
                'Unable to prepare passed exam certificate emails.',
                [
                    'exam_id' => $examId,
                    'certificate_ids' => $certificateIds,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Generate a generic, non-regulatory Certificate of Completion for an
     * Individual Request. It is generated directly as an email attachment and
     * does not depend on Company Request accreditations.
     */
    private function buildIndividualCertificateEmailAttachment(
        Exam $exam
    ): array {
        $exam->loadMissing([
            'enrollment.user',
            'enrollment.course.category',
            'enrollment.individualRequestCourse.individualRequest',
        ]);

        $enrollment = $exam->enrollment;
        $learner = $enrollment?->user;
        $course = $enrollment?->course;
        $individualItem = $enrollment?->individualRequestCourse;
        $individualRequest = $individualItem?->individualRequest;

        if (
            !$enrollment ||
            !$learner ||
            !$course ||
            !$individualItem
        ) {
            throw new \RuntimeException(
                'Individual certificate PDF data is incomplete.'
            );
        }

        $learnerDisplayName = $this->getUserDisplayName(
            $learner
        );

        $completedAt = $exam->submitted_at
            ?? $exam->completed_at
            ?? $enrollment->completed_at
            ?? now();

        $completedAtCarbon = Carbon::parse(
            $completedAt
        );

        $completedAtFormatted = $completedAtCarbon
            ->format('d-m-Y');

        $completedTimeFormatted = $completedAtCarbon
            ->format('H:i');

        $expiredAtFormatted = $enrollment->expired_at
            ? Carbon::parse($enrollment->expired_at)->format('d-m-Y')
            : null;

        $certificateNumber = sprintf(
            'IND-CERT-%s-%06d',
            $completedAtCarbon->format('Y'),
            $enrollment->id
        );

        $pdf = Pdf::loadView(
                'learner.certificates.individual-certificate',
                [
                    'certificateNumber' => $certificateNumber,
                    'individualRequest' => $individualRequest,
                    'individualItem' => $individualItem,
                    'enrollment' => $enrollment,
                    'learner' => $learner,
                    'learnerDisplayName' => $learnerDisplayName,
                    'course' => $course,
                    'exam' => $exam,
                    'completedAt' => $completedAtFormatted,
                    'completedTime' => $completedTimeFormatted,
                    'expiredAt' => $expiredAtFormatted,
                    'validUntil' => $expiredAtFormatted,
                    'validityMonths' => $enrollment->duree_validite_formation !== null
                        ? (int) $enrollment->duree_validite_formation
                        : null,
                    'hasExpiration' => $expiredAtFormatted !== null,
                ]
            )
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'enable_php' => true,
                /*
                 * The Individual certificate intentionally uses no remote image
                 * asset, which also avoids local ImageMagick/Imagick DLL issues.
                 */
                'isRemoteEnabled' => false,
            ]);

        $learnerName = Str::slug(
            $learnerDisplayName ?: 'learner'
        );

        $courseName = Str::slug(
            $course->title ?? 'training-course'
        );

        return [
            'name' => sprintf(
                'certificate-individual-%s-%s.pdf',
                $courseName,
                $learnerName
            ),
            'content' => $pdf->output(),
            'content_type' => 'application/pdf',
        ];
    }

    /**
     * Build one PDF attachment for each certificate generated for the exam.
     */
    private function buildCertificateEmailAttachments(
        Exam $exam,
        Collection $certificates
    ): array {
        $attachments = [];

        foreach ($certificates as $certificate) {
            try {
                $attachments[] = $this->buildCertificateEmailAttachment(
                    $exam,
                    $certificate
                );
            } catch (Throwable $exception) {
                Log::error(
                    'Unable to generate certificate PDF attachment.',
                    [
                        'exam_id' => $exam->id,
                        'certificate_id' => $certificate->id,
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $attachments;
    }

    /**
     * Generate the exact certificate PDF used by the learner download page,
     * but return it as binary content for an email attachment.
     */
    private function buildCertificateEmailAttachment(
        Exam $exam,
        Certificate $certificate
    ): array {
        $exam->loadMissing([
            'enrollment.user',
            'enrollment.course.category',
            'enrollment.companyRequest.accreditations',
        ]);

        $certificate->loadMissing('accreditation');

        $enrollment = $exam->enrollment;
        $learner = $enrollment?->user;
        $course = $enrollment?->course;
        $accreditation = $certificate->accreditation;

        if (!$enrollment || !$learner || !$course || !$accreditation) {
            throw new \RuntimeException(
                'Certificate PDF data is incomplete.'
            );
        }

        $learnerDisplayName = $this->getUserDisplayName(
            $learner
        );

        /* Backward compatibility for older certificate templates. */
        $learner->setAttribute(
            'name',
            $learnerDisplayName
        );

        $completedAt = $certificate->issued_at
            ?? $exam->submitted_at
            ?? $exam->completed_at
            ?? $enrollment->completed_at
            ?? now();

        $completedAtCarbon = Carbon::parse(
            $completedAt
        );

        $completedAtFormatted = $completedAtCarbon
            ->format('d-m-Y');

        $completedTimeFormatted = $completedAtCarbon
            ->format('H:i');

        $expiredAtFormatted = $enrollment->expired_at
            ? Carbon::parse($enrollment->expired_at)->format('d-m-Y')
            : null;

        $template = $this->resolveCertificateTemplate(
            $accreditation
        );

        $assets = $this->resolveCertificateAssets(
            $accreditation
        );

        $pdf = Pdf::loadView(
                $template,
                [
                    'certificateLogo' => $assets['logo'],
                    'imageBG' => $assets['background'],
                    'SECONDEpage' => $assets['second_page'],
                    'certificate' => $certificate,
                    'certificateNumber' => $certificate->certificate_number,
                    'accreditation' => $accreditation,
                    'trainingRequest' => $enrollment->companyRequest,
                    'enrollment' => $enrollment,
                    'learner' => $learner,
                    'learnerDisplayName' => $learnerDisplayName,
                    'course' => $course,
                    'exam' => $exam,
                    'completedAt' => $completedAtFormatted,
                    'completedTime' => $completedTimeFormatted,
                    'expiredAt' => $expiredAtFormatted,
                    'validUntil' => $expiredAtFormatted,
                    'validityMonths' => $enrollment->duree_validite_formation !== null
                        ? (int) $enrollment->duree_validite_formation
                        : null,
                    'hasExpiration' => $expiredAtFormatted !== null,
                ]
            )
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'enable_php' => true,
                'isRemoteEnabled' => true,
            ]);

        $learnerName = Str::slug(
            $learnerDisplayName ?: 'learner'
        );

        $courseName = Str::slug(
            $course->title ?? 'training-course'
        );

        $accreditationCode = Str::slug(
            $accreditation->code ?? 'certificate'
        );

        $fileName = sprintf(
            'certificate-%s-%s-%s.pdf',
            $accreditationCode,
            $courseName,
            $learnerName
        );

        return [
            'name' => $fileName,
            'content' => $pdf->output(),
            'content_type' => 'application/pdf',
        ];
    }

    private function normalizeExamAnswer(Question $question, ?string $answer): ?string
    {
        if (!$answer) {
            return null;
        }

        if ($answer === 'response1') {
            return $question->response1;
        }

        if ($answer === 'response2') {
            return $question->response2;
        }

        if ($answer === 'response3') {
            return $question->response3;
        }

        return $answer;
    }

    private function isExamAnswerCorrect(Question $question, ?string $rawAnswer, ?string $answerText): bool
    {
        if (!$rawAnswer || !$answerText) {
            return false;
        }

        $correctAnswer = mb_strtolower(trim((string) $question->correct_answer));
        $normalizedAnswerText = mb_strtolower(trim((string) $answerText));

        if ($normalizedAnswerText === $correctAnswer) {
            return true;
        }

        if (
            $rawAnswer === 'response1' &&
            mb_strtolower(trim((string) $question->response1)) === $correctAnswer
        ) {
            return true;
        }

        if (
            $rawAnswer === 'response2' &&
            mb_strtolower(trim((string) $question->response2)) === $correctAnswer
        ) {
            return true;
        }

        if (
            $rawAnswer === 'response3' &&
            mb_strtolower(trim((string) $question->response3)) === $correctAnswer
        ) {
            return true;
        }

        return false;
    }



    /**
     * Build the learner display name from the real users table columns.
     *
     * The users table does not contain a physical `name` column. Avoid using
     * `user:id,name,email` in eager loads because MySQL will try to select a
     * non-existent field and throw SQLSTATE[42S22].
     */
    private function getUserDisplayName($user): string
    {
        if (!$user) {
            return 'Learner';
        }

        $fullName = collect([
            $user->first_name ?? null,
            $user->middle_name ?? null,
            $user->last_name ?? null,
        ])
            ->filter(function ($part) {
                return filled($part);
            })
            ->map(function ($part) {
                return trim((string) $part);
            })
            ->implode(' ');

        if ($fullName !== '') {
            return $fullName;
        }

        return filled($user->email ?? null)
            ? (string) $user->email
            : 'Learner';
    }


    /**
     * Resolve the Blade template from the accreditation linked to the
     * certificate. File names are case-sensitive on Linux servers.
     */
   private function resolveCertificateAssets($accreditation): array {

        $code = strtolower(
            trim(
                (string) (
                    $accreditation?->code ?? ''
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Assets configuration
        |--------------------------------------------------------------------------
        */

        $assetsByAccreditation = [

            /*
            |--------------------------------------------------------------------------
            | DAC / DGAC MAROC
            |--------------------------------------------------------------------------
            */

            'dac' => [
                'logo' => resource_path(
                    'images/certificates/dac/logo.png'
                ),

                'background' => resource_path(
                    'images/certificates/dac/background.png'
                ),

                'second_page' => resource_path(
                    'images/certificates/dac/second-page.png'
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | EASA
            |--------------------------------------------------------------------------
            */

            'easa' => [
                'logo' => resource_path(
                    'images/certificates/easa/logo.png'
                ),

                'background' => resource_path(
                    'images/certificates/easa/background.png'
                ),

                'second_page' => resource_path(
                    'images/certificates/easa/second-page.png'
                ),
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Unknown accreditation
        |--------------------------------------------------------------------------
        */

        if (!isset($assetsByAccreditation[$code])) {
            abort(
                500,
                sprintf(
                    'Certificate assets are not configured for accreditation [%s].',
                    $code !== ''
                        ? strtoupper($code)
                        : 'UNKNOWN'
                )
            );
        }

        $configuration = $assetsByAccreditation[$code];

        /*
        |--------------------------------------------------------------------------
        | Convert images to Base64
        |--------------------------------------------------------------------------
        */

        return [
            'logo' => $this->certificateImageToBase64(
                $configuration['logo']
            ),

            'background' => $this->certificateImageToBase64(
                $configuration['background']
            ),

            'second_page' => $this->certificateImageToBase64(
                $configuration['second_page']
            ),
        ];
    }


    private function certificateImageToBase64(?string $path): ?string 
    {
        if (
            !$path ||
            !File::exists($path)
        ) {
            return null;
        }

        $extension = strtolower(
            pathinfo(
                $path,
                PATHINFO_EXTENSION
            )
        );

        $mimeType = match ($extension) {
            'jpg',
            'jpeg' => 'image/jpeg',

            'webp' => 'image/webp',

            'svg' => 'image/svg+xml',

            default => 'image/png',
        };

        return sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode(
                File::get($path)
            )
        );
    }

    private function resolveCertificateTemplate($accreditation): string 
    {

        $code = strtolower(
            trim(
                (string) (
                    $accreditation?->code ?? ''
                )
            )
        );

        $templatesByCode = [

            'easa' =>
                'learner.certificates.Easa-certificate',

            'dac' =>
                'learner.certificates.Dac-certificate',

        ];

        /*
        |--------------------------------------------------------------------------
        | Template registered in code
        |--------------------------------------------------------------------------
        */

        $template = $templatesByCode[$code] ?? null;

        if (
            $template &&
            View::exists($template)
        ) {
            return $template;
        }

        /*
        |--------------------------------------------------------------------------
        | Template configured from database
        |--------------------------------------------------------------------------
        */

        $configuredTemplate = trim(
            (string) (
                $accreditation?->certificate_template ?? ''
            )
        );

        if (
            $configuredTemplate !== '' &&
            View::exists($configuredTemplate)
        ) {
            return $configuredTemplate;
        }

        /*
        |--------------------------------------------------------------------------
        | Nothing found
        |--------------------------------------------------------------------------
        */

        abort(
            500,
            sprintf(
                'Certificate template not found for accreditation [%s].',
                $code !== ''
                    ? strtoupper($code)
                    : 'UNKNOWN'
            )
        );
    }


}