<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\CompanyRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class VelzonRoutesController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user && $user->hasAnyRole(['Super-admin'])) {
            return $this->renderSuperAdminDashboard($user);
        }

        if ($user && $user->hasAnyRole(['Admin'])) {
            return $this->renderCompanyAdminDashboard($user);
        }

        if ($user && $user->hasAnyRole(['Learner'])) {
            return redirect()->route('learner-space.dashboard');
        }

        return Inertia::render('Public/Home');
    }

    private function renderSuperAdminDashboard(User $user)
    {
        /*
        |--------------------------------------------------------------------------
        | Global statistics
        |--------------------------------------------------------------------------
        */
        $companiesQuery = Company::query();

        $companiesStats = [
            'total' => (clone $companiesQuery)->count(),
            'active' => (clone $companiesQuery)
                ->where('status', 'active')
                ->count(),
            'inactive' => (clone $companiesQuery)
                ->where('status', '!=', 'active')
                ->count(),
        ];

        $adminsQuery = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'admin']);
            });

        $learnersQuery = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Learner', 'learner']);
            });

        $usersStats = [
            'total' => User::query()->count(),
            'admins' => (clone $adminsQuery)->count(),
            'learners' => (clone $learnersQuery)->count(),
            'active' => User::query()->where('status', 'active')->count(),
            'inactive' => User::query()->where('status', '!=', 'active')->count(),
        ];

        $requestsQuery = CompanyRequest::query();

        $requestsStats = [
            'total' => (clone $requestsQuery)->count(),
            'pending' => (clone $requestsQuery)
                ->where('status', 'pending')
                ->count(),
            'approved' => (clone $requestsQuery)
                ->where('status', 'approved')
                ->count(),
            'rejected' => (clone $requestsQuery)
                ->where('status', 'rejected')
                ->count(),
        ];

        $enrollmentsQuery = Enrollment::query();

        $enrollmentsStats = [
            'total' => (clone $enrollmentsQuery)->count(),
            'not_started' => (clone $enrollmentsQuery)
                ->where('status', 'not_started')
                ->count(),
            'in_progress' => (clone $enrollmentsQuery)
                ->where('status', 'in_progress')
                ->count(),
            'completed' => (clone $enrollmentsQuery)
                ->where('status', 'completed')
                ->count(),
            'expired' => (clone $enrollmentsQuery)
                ->where('status', 'expired')
                ->count(),
            'with_expiration' => (clone $enrollmentsQuery)
                ->whereNotNull('duree_validite_formation')
                ->count(),
            'without_expiration' => (clone $enrollmentsQuery)
                ->whereNull('duree_validite_formation')
                ->count(),
            'average_progress' => round(
                (float) ((clone $enrollmentsQuery)->avg('progress_percent') ?? 0),
                2
            ),
        ];

        $coursesQuery = Course::query();

        $coursesStats = [
            'total' => (clone $coursesQuery)->count(),
            'published' => (clone $coursesQuery)
                ->where('is_published', true)
                ->count(),
            'draft' => (clone $coursesQuery)
                ->where('is_published', false)
                ->count(),
            'normal' => (clone $coursesQuery)
                ->where(function ($query) {
                    $query->where('content_type', 'normal')
                        ->orWhereNull('content_type');
                })
                ->count(),
            'scorm' => (clone $coursesQuery)
                ->where('content_type', 'scorm')
                ->count(),
        ];

        $examsQuery = Exam::query();

        $examsStats = [
            'total' => (clone $examsQuery)->count(),
            'passed' => (clone $examsQuery)
                ->where(function ($query) {
                    $query->where('passed', true)
                        ->orWhere('status', 'passed');
                })
                ->count(),
            'failed' => (clone $examsQuery)
                ->where('status', 'failed')
                ->count(),
            'in_progress' => (clone $examsQuery)
                ->where('status', 'in_progress')
                ->count(),
        ];

        $certificatesCount = Certificate::query()->count();

        /*
        |--------------------------------------------------------------------------
        | Per-company aggregates
        |--------------------------------------------------------------------------
        */
        $adminCounts = User::query()
            ->whereNotNull('company_id')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'admin']);
            })
            ->select('company_id', DB::raw('COUNT(*) as total'))
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $learnerCounts = User::query()
            ->whereNotNull('company_id')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Learner', 'learner']);
            })
            ->select('company_id', DB::raw('COUNT(*) as total'))
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $requestMetrics = CompanyRequest::query()
            ->select([
                'company_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
            ])
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $enrollmentMetrics = Enrollment::query()
            ->whereNotNull('company_id')
            ->select([
                'company_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT course_id) as courses_count'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw('AVG(progress_percent) as average_progress'),
            ])
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $companyCollection = Company::query()
            ->latest()
            ->get();

        $companies = $companyCollection
            ->map(function ($company) use (
                $adminCounts,
                $learnerCounts,
                $requestMetrics,
                $enrollmentMetrics
            ) {
                $requestStat = $requestMetrics->get($company->id);
                $enrollmentStat = $enrollmentMetrics->get($company->id);
                $enrollmentsCount = (int) ($enrollmentStat?->total ?? 0);
                $completedCount = (int) ($enrollmentStat?->completed ?? 0);

                return [
                    'id' => $company->id,
                    'hashid' => Hashids::encode($company->id),
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'status' => $company->status,
                    'logo' => $company->logo,
                    'logo_url' => $company->logo
                        ? asset('storage/' . $company->logo)
                        : null,
                    'created_at' => $company->created_at,
                    'admins_count' => (int) ($adminCounts->get($company->id) ?? 0),
                    'learners_count' => (int) ($learnerCounts->get($company->id) ?? 0),
                    'requests_count' => (int) ($requestStat?->total ?? 0),
                    'pending_requests' => (int) ($requestStat?->pending ?? 0),
                    'approved_requests' => (int) ($requestStat?->approved ?? 0),
                    'rejected_requests' => (int) ($requestStat?->rejected ?? 0),
                    'enrollments_count' => $enrollmentsCount,
                    'assigned_courses' => (int) ($enrollmentStat?->courses_count ?? 0),
                    'completed_enrollments' => $completedCount,
                    'in_progress_enrollments' => (int) ($enrollmentStat?->in_progress ?? 0),
                    'average_progress' => round(
                        (float) ($enrollmentStat?->average_progress ?? 0),
                        2
                    ),
                    'completion_rate' => $enrollmentsCount > 0
                        ? round(($completedCount / $enrollmentsCount) * 100, 2)
                        : 0,
                    'action_url' => $this->resolveCompanyActionUrl($company),
                ];
            })
            ->values();

        $topCompanies = $companies
            ->sort(function ($first, $second) {
                $enrollmentComparison = $second['enrollments_count']
                    <=> $first['enrollments_count'];

                if ($enrollmentComparison !== 0) {
                    return $enrollmentComparison;
                }

                return $second['completion_rate']
                    <=> $first['completion_rate'];
            })
            ->take(6)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Pending requests
        |--------------------------------------------------------------------------
        */
        $pendingRequests = CompanyRequest::query()
            ->with([
                'company:id,name,logo,status',
                'course:id,title,image,content_type,training_type',
                'requestedBy:id,first_name,middle_name,last_name,email',
            ])
            ->withCount('users')
            ->where('status', 'pending')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($companyRequest) {
                return [
                    'id' => $companyRequest->id,
                    'hashid' => Hashids::encode($companyRequest->id),
                    'reference' => $companyRequest->reference,
                    'status' => $companyRequest->status,
                    'notes' => $companyRequest->notes,
                    'start_date' => $companyRequest->start_date,
                    'end_date' => $companyRequest->end_date,
                    'duree_validite_formation' => $companyRequest->duree_validite_formation !== null
                        ? (int) $companyRequest->duree_validite_formation
                        : null,
                    'learners_count' => (int) $companyRequest->users_count,
                    'created_at' => $companyRequest->created_at,
                    'company' => $companyRequest->company ? [
                        'id' => $companyRequest->company->id,
                        'name' => $companyRequest->company->name,
                        'status' => $companyRequest->company->status,
                        'logo_url' => $companyRequest->company->logo
                            ? asset('storage/' . $companyRequest->company->logo)
                            : null,
                    ] : null,
                    'course' => $companyRequest->course ? [
                        'id' => $companyRequest->course->id,
                        'title' => $companyRequest->course->title,
                        'content_type' => $companyRequest->course->content_type ?? 'normal',
                        'training_type' => $companyRequest->course->training_type,
                        'image_url' => $companyRequest->course->image
                            ? asset('storage/' . $companyRequest->course->image)
                            : null,
                    ] : null,
                    'requested_by' => $companyRequest->requestedBy ? [
                        'id' => $companyRequest->requestedBy->id,
                        'first_name' => $companyRequest->requestedBy->first_name,
                        'middle_name' => $companyRequest->requestedBy->middle_name,
                        'last_name' => $companyRequest->requestedBy->last_name,
                        'name' => $this->formatUserName($companyRequest->requestedBy),
                        'email' => $companyRequest->requestedBy->email,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Recent enrollments across all companies
        |--------------------------------------------------------------------------
        */
        $companiesById = $companyCollection->keyBy('id');

        $recentEnrollments = Enrollment::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email,profile_photo_path',
                'course:id,title,image,content_type,training_type',
            ])
            ->whereNotNull('company_id')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($enrollment) use ($companiesById) {
                $company = $companiesById->get($enrollment->company_id);

                return [
                    'id' => $enrollment->id,
                    'hashid' => $enrollment->hashid ?? Hashids::encode($enrollment->id),
                    'status' => $enrollment->status,
                    'progress_percent' => (float) ($enrollment->progress_percent ?? 0),
                    'attempts_rest' => (int) ($enrollment->attempts_rest ?? 0),
                    'started_at' => $enrollment->started_at,
                    'completed_at' => $enrollment->completed_at,
                    'expired_at' => $enrollment->expired_at,
                    'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                        ? (int) $enrollment->duree_validite_formation
                        : null,
                    'has_expiration' => $enrollment->duree_validite_formation !== null,
                    'created_at' => $enrollment->created_at,
                    'company' => $company ? [
                        'id' => $company->id,
                        'name' => $company->name,
                        'logo_url' => $company->logo
                            ? asset('storage/' . $company->logo)
                            : null,
                    ] : null,
                    'learner' => $enrollment->user ? [
                        'id' => $enrollment->user->id,
                        'first_name' => $enrollment->user->first_name,
                        'middle_name' => $enrollment->user->middle_name,
                        'last_name' => $enrollment->user->last_name,
                        'name' => $this->formatUserName($enrollment->user),
                        'email' => $enrollment->user->email,
                        'photo_url' => $enrollment->user->profile_photo_url
                            ?? ($enrollment->user->profile_photo_path
                                ? asset('storage/' . $enrollment->user->profile_photo_path)
                                : null),
                    ] : null,
                    'course' => $enrollment->course ? [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                        'content_type' => $enrollment->course->content_type ?? 'normal',
                        'training_type' => $enrollment->course->training_type,
                        'image_url' => $enrollment->course->image
                            ? asset('storage/' . $enrollment->course->image)
                            : null,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Six-month platform activity
        |--------------------------------------------------------------------------
        */
        $firstMonth = now()->copy()->subMonths(5)->startOfMonth();

        $companyActivity = Company::query()
            ->where('created_at', '>=', $firstMonth)
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'))
            ->map->count();

        $requestActivity = CompanyRequest::query()
            ->where('created_at', '>=', $firstMonth)
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'))
            ->map->count();

        $enrollmentActivity = Enrollment::query()
            ->where('created_at', '>=', $firstMonth)
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'))
            ->map->count();

        $monthlyActivity = collect(range(5, 0))
            ->map(function ($monthsAgo) use (
                $companyActivity,
                $requestActivity,
                $enrollmentActivity
            ) {
                $month = now()->copy()->subMonths($monthsAgo)->startOfMonth();
                $key = $month->format('Y-m');

                return [
                    'key' => $key,
                    'label' => $month->format('M Y'),
                    'companies' => (int) ($companyActivity->get($key) ?? 0),
                    'requests' => (int) ($requestActivity->get($key) ?? 0),
                    'enrollments' => (int) ($enrollmentActivity->get($key) ?? 0),
                ];
            })
            ->values();

        return Inertia::render('dashboards/ecommerce/index', [
            'stats' => [
                'companies' => $companiesStats,
                'users' => $usersStats,
                'requests' => $requestsStats,
                'enrollments' => $enrollmentsStats,
                'courses' => $coursesStats,
                'exams' => $examsStats,
                'certificates' => $certificatesCount,
            ],
            'companies' => $companies,
            'topCompanies' => $topCompanies,
            'pendingRequests' => $pendingRequests,
            'recentEnrollments' => $recentEnrollments,
            'monthlyActivity' => $monthlyActivity,
            'links' => $this->superAdminDashboardLinks(),
            'permissions' => $this->superAdminDashboardPermissions($user),
        ]);
    }

    private function resolveCompanyActionUrl(Company $company): ?string
    {
        $hashid = Hashids::encode($company->id);

        if (Route::has('companies.show')) {
            return route('companies.show', $hashid);
        }

        if (Route::has('companies.edit')) {
            return route('companies.edit', $hashid);
        }

        return null;
    }

    private function superAdminDashboardLinks(): array
    {
        return [
            'companies' => Route::has('companies.index')
                ? route('companies.index')
                : '#',
            'create_company' => Route::has('companies.create')
                ? route('companies.create')
                : '#',
            'users' => Route::has('users.index')
                ? route('users.index')
                : '#',
            'learners' => Route::has('learners.index')
                ? route('learners.index')
                : '#',
            'requests' => Route::has('company-requests.index')
                ? route('company-requests.index')
                : '#',
            'courses' => Route::has('courses.index')
                ? route('courses.index')
                : '#',
        ];
    }

    private function superAdminDashboardPermissions(User $user): array
    {
        return [
            'read_companies' => $user->can('read company'),
            'create_company' => $user->can('create company'),
            'update_company' => $user->can('update company'),
            'read_users' => $user->can('read user'),
            'read_requests' => $user->can('read company request'),
            'approve_requests' => $user->can('approve company request'),
            'read_courses' => $user->can('read course'),
        ];
    }

    private function renderCompanyAdminDashboard(User $user)
    {
        if (!$user->company_id) {
            return Inertia::render('dashboards/adminDashbord', [
                'missingCompany' => true,
                'company' => null,
                'stats' => $this->emptyCompanyStats(),
                'recentRequests' => [],
                'recentLearners' => [],
                'recentEnrollments' => [],
                'links' => $this->companyDashboardLinks(),
                'permissions' => $this->companyDashboardPermissions($user),
            ]);
        }

        $companyId = (int) $user->company_id;

        $company = Company::query()->findOrFail($companyId);

        /*
        |--------------------------------------------------------------------------
        | Learners statistics
        |--------------------------------------------------------------------------
        */
        $learnersQuery = User::query()
            ->where('company_id', $companyId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Learner', 'learner']);
            });

        $learnersStats = [
            'total' => (clone $learnersQuery)->count(),
            'active' => (clone $learnersQuery)
                ->where('status', 'active')
                ->count(),
            'inactive' => (clone $learnersQuery)
                ->where('status', '!=', 'active')
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Company requests statistics
        |--------------------------------------------------------------------------
        */
        $requestsQuery = CompanyRequest::query()
            ->where('company_id', $companyId);

        $requestsStats = [
            'total' => (clone $requestsQuery)->count(),
            'pending' => (clone $requestsQuery)
                ->where('status', 'pending')
                ->count(),
            'approved' => (clone $requestsQuery)
                ->where('status', 'approved')
                ->count(),
            'rejected' => (clone $requestsQuery)
                ->where('status', 'rejected')
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Enrollments statistics
        |--------------------------------------------------------------------------
        */
        $enrollmentsQuery = Enrollment::query()
            ->where('company_id', $companyId);

        $enrollmentsStats = [
            'total' => (clone $enrollmentsQuery)->count(),
            'not_started' => (clone $enrollmentsQuery)
                ->where('status', 'not_started')
                ->count(),
            'in_progress' => (clone $enrollmentsQuery)
                ->where('status', 'in_progress')
                ->count(),
            'completed' => (clone $enrollmentsQuery)
                ->where('status', 'completed')
                ->count(),
            'expired' => (clone $enrollmentsQuery)
                ->where('status', 'expired')
                ->count(),
            'with_expiration' => (clone $enrollmentsQuery)
                ->whereNotNull('duree_validite_formation')
                ->count(),
            'without_expiration' => (clone $enrollmentsQuery)
                ->whereNull('duree_validite_formation')
                ->count(),
            'assigned_courses' => (clone $enrollmentsQuery)
                ->distinct()
                ->count('course_id'),
            'average_progress' => round(
                (float) ((clone $enrollmentsQuery)->avg('progress_percent') ?? 0),
                2
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Exam and certificate statistics
        |--------------------------------------------------------------------------
        */
        $examsQuery = Exam::query()
            ->whereHas('enrollment', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });

        $examStats = [
            'total' => (clone $examsQuery)->count(),
            'passed' => (clone $examsQuery)
                ->where(function ($query) {
                    $query->where('passed', true)
                        ->orWhere('status', 'passed');
                })
                ->count(),
            'failed' => (clone $examsQuery)
                ->where('status', 'failed')
                ->count(),
            'in_progress' => (clone $examsQuery)
                ->where('status', 'in_progress')
                ->count(),
        ];

        $certificatesCount = Certificate::query()
            ->whereHas('enrollment', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent requests
        |--------------------------------------------------------------------------
        */
        $recentRequests = CompanyRequest::query()
            ->with([
                'course:id,title,image,content_type,training_type',
                'requestedBy:id,first_name,middle_name,last_name,email',
            ])
            ->withCount('users')
            ->where('company_id', $companyId)
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($companyRequest) {
                return [
                    'id' => $companyRequest->id,
                    'hashid' => Hashids::encode($companyRequest->id),
                    'reference' => $companyRequest->reference,
                    'status' => $companyRequest->status,
                    'notes' => $companyRequest->notes,
                    'start_date' => $companyRequest->start_date,
                    'end_date' => $companyRequest->end_date,
                    'duree_validite_formation' => $companyRequest->duree_validite_formation !== null
                        ? (int) $companyRequest->duree_validite_formation
                        : null,
                    'learners_count' => (int) $companyRequest->users_count,
                    'created_at' => $companyRequest->created_at,
                    'validated_at' => $companyRequest->validated_at,

                    'course' => $companyRequest->course ? [
                        'id' => $companyRequest->course->id,
                        'title' => $companyRequest->course->title,
                        'content_type' => $companyRequest->course->content_type ?? 'normal',
                        'training_type' => $companyRequest->course->training_type,
                        'image' => $companyRequest->course->image,
                        'image_url' => $companyRequest->course->image_url
                            ?? ($companyRequest->course->image
                                ? asset('storage/' . $companyRequest->course->image)
                                : null),
                    ] : null,

                    'requested_by' => $companyRequest->requestedBy ? [
                        'id' => $companyRequest->requestedBy->id,
                        'first_name' => $companyRequest->requestedBy->first_name,
                        'middle_name' => $companyRequest->requestedBy->middle_name,
                        'last_name' => $companyRequest->requestedBy->last_name,
                        'name' => $this->formatUserName($companyRequest->requestedBy),
                        'email' => $companyRequest->requestedBy->email,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Recent learners
        |--------------------------------------------------------------------------
        */
        $recentLearnersCollection = (clone $learnersQuery)
            ->latest()
            ->limit(6)
            ->get();

        $recentLearnerIds = $recentLearnersCollection
            ->pluck('id')
            ->values();

        $learnerEnrollmentStats = Enrollment::query()
            ->where('company_id', $companyId)
            ->whereIn('user_id', $recentLearnerIds)
            ->select([
                'user_id',
                DB::raw('COUNT(*) as enrollments_count'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_courses"),
                DB::raw('AVG(progress_percent) as average_progress'),
            ])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $recentLearners = $recentLearnersCollection
            ->map(function ($learner) use ($learnerEnrollmentStats) {
                $statistics = $learnerEnrollmentStats->get($learner->id);

                return [
                    'id' => $learner->id,
                    'first_name' => $learner->first_name,
                    'middle_name' => $learner->middle_name,
                    'last_name' => $learner->last_name,
                    'name' => $this->formatUserName($learner),
                    'email' => $learner->email,
                    'status' => $learner->status,
                    'country' => $learner->country,
                    'created_at' => $learner->created_at,
                    'photo_url' => $learner->profile_photo_url
                        ?? ($learner->profile_photo_path
                            ? asset('storage/' . $learner->profile_photo_path)
                            : null),
                    'enrollments_count' => (int) ($statistics?->enrollments_count ?? 0),
                    'completed_courses' => (int) ($statistics?->completed_courses ?? 0),
                    'average_progress' => round(
                        (float) ($statistics?->average_progress ?? 0),
                        2
                    ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Recent enrollments
        |--------------------------------------------------------------------------
        */
        $recentEnrollments = Enrollment::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email,profile_photo_path',
                'course:id,title,image,content_type,training_type',
            ])
            ->where('company_id', $companyId)
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'hashid' => $enrollment->hashid ?? Hashids::encode($enrollment->id),
                    'status' => $enrollment->status,
                    'progress_percent' => (float) ($enrollment->progress_percent ?? 0),
                    'attempts_rest' => (int) ($enrollment->attempts_rest ?? 0),
                    'started_at' => $enrollment->started_at,
                    'completed_at' => $enrollment->completed_at,
                    'expired_at' => $enrollment->expired_at,
                    'duree_validite_formation' => $enrollment->duree_validite_formation !== null
                        ? (int) $enrollment->duree_validite_formation
                        : null,
                    'has_expiration' => $enrollment->duree_validite_formation !== null,
                    'created_at' => $enrollment->created_at,

                    'learner' => $enrollment->user ? [
                        'id' => $enrollment->user->id,
                        'first_name' => $enrollment->user->first_name,
                        'middle_name' => $enrollment->user->middle_name,
                        'last_name' => $enrollment->user->last_name,
                        'name' => $this->formatUserName($enrollment->user),
                        'email' => $enrollment->user->email,
                        'photo_url' => $enrollment->user->profile_photo_url
                            ?? ($enrollment->user->profile_photo_path
                                ? asset('storage/' . $enrollment->user->profile_photo_path)
                                : null),
                    ] : null,

                    'course' => $enrollment->course ? [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                        'content_type' => $enrollment->course->content_type ?? 'normal',
                        'training_type' => $enrollment->course->training_type,
                        'image' => $enrollment->course->image,
                        'image_url' => $enrollment->course->image_url
                            ?? ($enrollment->course->image
                                ? asset('storage/' . $enrollment->course->image)
                                : null),
                    ] : null,
                ];
            })
            ->values();

        return Inertia::render('dashboards/adminDashbord', [
            'missingCompany' => false,

            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'status' => $company->status,
                'logo' => $company->logo,
                'logo_url' => $company->logo
                    ? asset('storage/' . $company->logo)
                    : null,
            ],

            'stats' => [
                'learners' => $learnersStats,
                'requests' => $requestsStats,
                'enrollments' => $enrollmentsStats,
                'exams' => $examStats,
                'certificates' => $certificatesCount,
            ],

            'recentRequests' => $recentRequests,
            'recentLearners' => $recentLearners,
            'recentEnrollments' => $recentEnrollments,
            'links' => $this->companyDashboardLinks(),
            'permissions' => $this->companyDashboardPermissions($user),
        ]);
    }

    private function emptyCompanyStats(): array
    {
        return [
            'learners' => [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ],
            'requests' => [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ],
            'enrollments' => [
                'total' => 0,
                'not_started' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'expired' => 0,
                'with_expiration' => 0,
                'without_expiration' => 0,
                'assigned_courses' => 0,
                'average_progress' => 0,
            ],
            'exams' => [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'in_progress' => 0,
            ],
            'certificates' => 0,
        ];
    }

    private function companyDashboardLinks(): array
    {
        return [
            'learners' => Route::has('users.index')
                ? route('users.index', ['role' => 'Learner'])
                : '#',
            'requests' => Route::has('company-requests.index')
                ? route('company-requests.index')
                : '#',
            'create_request' => Route::has('company-requests.create')
                ? route('company-requests.create')
                : '#',
        ];
    }

    private function companyDashboardPermissions(User $user): array
    {
        return [
            'read_learners' => $user->can('read user'),
            'create_learner' => $user->can('create user'),
            'read_requests' => $user->can('read company request'),
            'create_request' => $user->can('create company request'),
        ];
    }

    public function courses()
    {
        $baseCoursesQuery = Course::query()
            ->where('is_published', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            });

        $courses = (clone $baseCoursesQuery)
            ->with('category')
            ->latest()
            ->get()
            ->map(fn ($course) => $this->mapCourseCard($course))
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('courses', function ($query) {
                $query->where('is_published', true);
            })
            ->withCount([
                'courses as courses_count' => function ($query) {
                    $query->where('is_published', true);
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'courses_count' => $category->courses_count,
                ];
            })
            ->values();

        $filterOptions = [
            'training_types' => (clone $baseCoursesQuery)
                ->whereNotNull('training_type')
                ->where('training_type', '!=', '')
                ->distinct()
                ->pluck('training_type')
                ->values(),

            'methods' => (clone $baseCoursesQuery)
                ->whereNotNull('method')
                ->where('method', '!=', '')
                ->distinct()
                ->pluck('method')
                ->values(),

            'content_types' => (clone $baseCoursesQuery)
                ->selectRaw("COALESCE(content_type, 'normal') as content_type")
                ->distinct()
                ->pluck('content_type')
                ->values(),

            'skill_levels' => (clone $baseCoursesQuery)
                ->whereNotNull('skill_level')
                ->where('skill_level', '!=', '')
                ->distinct()
                ->pluck('skill_level')
                ->values(),

            'course_focuses' => (clone $baseCoursesQuery)
                ->whereNotNull('course_focus')
                ->where('course_focus', '!=', '')
                ->distinct()
                ->pluck('course_focus')
                ->values(),

            'types' => (clone $baseCoursesQuery)
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->distinct()
                ->pluck('type')
                ->values(),

            'languages' => (clone $baseCoursesQuery)
                ->whereNotNull('language')
                ->where('language', '!=', '')
                ->distinct()
                ->pluck('language')
                ->values(),
        ];

        return Inertia::render('Public/Courses', [
            'courses' => $courses,
            'categories' => $categories,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function courseDetails(string $courseHashid)
    {
        $decodedCourseIds = Hashids::decode($courseHashid);

        if (empty($decodedCourseIds)) {
            abort(404);
        }

        $courseId = $decodedCourseIds[0];

        $course = Course::query()
            ->where('id', $courseId)
            ->where('is_published', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->with([
                'category',
                'tags',
                'chapters' => function ($query) {
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
            ->firstOrFail();

        $chapters = $course->chapters
            ->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'sort_order' => $chapter->sort_order,
                    'slides_count' => $chapter->slides->count(),
                    'slides' => $chapter->slides
                        ->map(function ($slide) {
                            return [
                                'id' => $slide->id,
                                'title' => $slide->title,
                                'lesson_type' => $slide->lesson_type,
                                'is_preview' => (bool) $slide->is_preview,
                                'is_mandatory' => (bool) $slide->is_mandatory,
                                'sort_order' => $slide->sort_order,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        $relatedCourses = Course::query()
            ->where('is_published', true)
            ->where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->with('category')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($relatedCourse) => $this->mapCourseCard($relatedCourse))
            ->values();

        return Inertia::render('Public/CourseDetails', [
            'course' => [
                'id' => $course->id,
                'hashid' => $course->hashid,
                'ref' => $course->ref,
                'title' => $course->title,
                'description' => $course->description,
                'content_type' => $course->content_type ?? 'normal',
                'type' => $course->type,
                'training_type' => $course->training_type,
                'method' => $course->method,
                'duration_hours' => $course->duration_hours,
                'passing_score' => $course->passing_score,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'language' => $course->language,
                'price' => $course->price,
                'currency' => $course->currency,
                'image_url' => $course->image_url,
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,
                'support_voice_over' => (bool) $course->support_voice_over,
                'voice_over_auto_play' => (bool) $course->voice_over_auto_play,
                'scorm_version' => $course->scorm_version,
                'scorm_identifier' => $course->scorm_identifier,
                'scorm_title' => $course->scorm_title,
                'category' => $course->category ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ] : null,
                'tags' => $course->tags
                    ->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    })
                    ->values(),
                'chapters' => $chapters,
                'chapters_count' => $chapters->count(),
                'lessons_count' => $chapters->sum('slides_count'),
                'request_url' => auth()->check()
                    ? route('public.individual-requests.create')
                    : route('learner.register'),
            ],
            'relatedCourses' => $relatedCourses,
        ]);
    }

    private function formatUserName(User $user): string
    {
        return collect([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(' ');
    }

    private function mapCourseCard(Course $course): array
    {
        return [
            'id' => $course->id,
            'hashid' => $course->hashid,
            'ref' => $course->ref,
            'title' => $course->title,
            'description' => $course->description,
            'content_type' => $course->content_type ?? 'normal',
            'type' => $course->type,
            'training_type' => $course->training_type,
            'method' => $course->method,
            'duration_hours' => $course->duration_hours,
            'passing_score' => $course->passing_score,
            'skill_level' => $course->skill_level,
            'course_focus' => $course->course_focus,
            'language' => $course->language,
            'price' => $course->price,
            'currency' => $course->currency,
            'image_url' => $course->image_url,
            'syllabus_url' => $course->syllabus
                ? asset('storage/' . $course->syllabus)
                : null,
            'support_voice_over' => (bool) $course->support_voice_over,
            'voice_over_auto_play' => (bool) $course->voice_over_auto_play,
            'scorm_version' => $course->scorm_version,
            'category' => $course->category ? [
                'id' => $course->category->id,
                'name' => $course->category->name,
            ] : null,
        ];
    }

    public function pages_maintenance()
    {
        return Inertia::render('pages/maintenance');
    }

    public function auth_404()
    {
        return Inertia::render('auth-pages/errors/404');
    }

    public function auth_500()
    {
        return Inertia::render('auth-pages/errors/500');
    }

    public function auth_404_basic()
    {
        return Inertia::render('auth-pages/errors/404-basic');
    }

    public function auth_404_cover()
    {
        return Inertia::render('auth-pages/errors/404-cover');
    }
}
