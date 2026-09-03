<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('companies/indexCompany', [
            'companies' => $companies,
        ]);
    }

    public function show(string $hashid)
    {
        $decoded = Hashids::decode($hashid);
        abort_if(empty($decoded), 404);

        $company = Company::findOrFail($decoded[0]);

        $requestsQuery = DB::table('company_requests')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at');

        $enrollmentsQuery = DB::table('enrollments')
            ->where('company_id', $company->id);

        $certificatesQuery = DB::table('certificates as cert')
            ->join('enrollments as e', 'e.id', '=', 'cert.enrollment_id')
            ->where('e.company_id', $company->id)
            ->whereNull('cert.deleted_at');

        $examsQuery = DB::table('exams as ex')
            ->join('enrollments as e', 'e.id', '=', 'ex.enrollment_id')
            ->where('e.company_id', $company->id)
            ->whereNull('ex.deleted_at');

        $usersCount = DB::table('users')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->count();

        $requestsCount = (clone $requestsQuery)->count();
        $pendingRequestsCount = (clone $requestsQuery)->where('status', 'pending')->count();
        $approvedRequestsCount = (clone $requestsQuery)->where('status', 'approved')->count();
        $rejectedRequestsCount = (clone $requestsQuery)->where('status', 'rejected')->count();

        $requestedCoursesCount = (clone $requestsQuery)
            ->whereNotNull('course_id')
            ->distinct()
            ->count('course_id');

        $approvedCoursesCount = (clone $requestsQuery)
            ->where('status', 'approved')
            ->whereNotNull('course_id')
            ->distinct()
            ->count('course_id');

        $enrollmentsCount = (clone $enrollmentsQuery)->count();

        $learnersEnrolledCount = (clone $enrollmentsQuery)
            ->distinct()
            ->count('user_id');

        $assignedCoursesCount = (clone $enrollmentsQuery)
            ->distinct()
            ->count('course_id');

        $notStartedEnrollmentsCount = (clone $enrollmentsQuery)
            ->where('status', 'not_started')
            ->count();

        $inProgressEnrollmentsCount = (clone $enrollmentsQuery)
            ->where('status', 'in_progress')
            ->count();

        $completedEnrollmentsCount = (clone $enrollmentsQuery)
            ->where('status', 'completed')
            ->count();

        $expiredEnrollmentsCount = (clone $enrollmentsQuery)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->count();

        $averageProgress = round(
            (float) ((clone $enrollmentsQuery)->avg('progress_percent') ?? 0),
            2
        );

        $certificatesIssuedCount = (clone $certificatesQuery)
            ->whereNotNull('cert.issued_at')
            ->count();

        $accreditationsUsedCount = (clone $certificatesQuery)
            ->whereNotNull('cert.issued_at')
            ->whereNotNull('cert.accreditation_id')
            ->distinct()
            ->count('cert.accreditation_id');

        $finishedExamAttempts = (clone $examsQuery)
            ->whereNotNull('ex.submitted_at')
            ->count();

        $passedExamAttempts = (clone $examsQuery)
            ->whereNotNull('ex.submitted_at')
            ->where('ex.passed', true)
            ->count();

        $failedExamAttempts = max(0, $finishedExamAttempts - $passedExamAttempts);

        $requestApprovalRate = $requestsCount > 0
            ? round(($approvedRequestsCount / $requestsCount) * 100, 2)
            : 0;

        $completionRate = $enrollmentsCount > 0
            ? round(($completedEnrollmentsCount / $enrollmentsCount) * 100, 2)
            : 0;

        $examPassRate = $finishedExamAttempts > 0
            ? round(($passedExamAttempts / $finishedExamAttempts) * 100, 2)
            : 0;

        $recentRequests = DB::table('company_requests as cr')
            ->leftJoin('courses as c', 'c.id', '=', 'cr.course_id')
            ->where('cr.company_id', $company->id)
            ->whereNull('cr.deleted_at')
            ->select([
                'cr.id',
                'cr.reference',
                'cr.status',
                'cr.start_date',
                'cr.end_date',
                'cr.duree_validite_formation',
                'cr.created_at',
                'c.ref as course_ref',
                'c.title as course_title',
                DB::raw('(SELECT COUNT(*) FROM company_request_user cru WHERE cru.company_request_id = cr.id) as learners_count'),
                DB::raw('(SELECT COUNT(*) FROM accreditation_company_request acr WHERE acr.company_request_id = cr.id) as accreditations_count'),
            ])
            ->orderByDesc('cr.created_at')
            ->limit(10)
            ->get();

        $courseStats = DB::table('enrollments as e')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->where('e.company_id', $company->id)
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.ref', 'c.title')
            ->select([
                'c.id',
                'c.ref',
                'c.title',
            ])
            ->selectRaw('COUNT(e.id) as enrollments_count')
            ->selectRaw('COUNT(DISTINCT e.user_id) as learners_count')
            ->selectRaw("SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw('ROUND(AVG(e.progress_percent), 2) as average_progress')
            ->orderByDesc('enrollments_count')
            ->get();

        $certificatesByCourse = DB::table('certificates as cert')
            ->join('enrollments as e', 'e.id', '=', 'cert.enrollment_id')
            ->where('e.company_id', $company->id)
            ->whereNull('cert.deleted_at')
            ->whereNotNull('cert.issued_at')
            ->groupBy('e.course_id')
            ->select('e.course_id', DB::raw('COUNT(cert.id) as total'))
            ->pluck('total', 'e.course_id');

        $requestsByCourse = DB::table('company_requests')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->whereNotNull('course_id')
            ->groupBy('course_id')
            ->select('course_id', DB::raw('COUNT(id) as total'))
            ->pluck('total', 'course_id');

        $approvedRequestsByCourse = DB::table('company_requests')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->whereNotNull('course_id')
            ->groupBy('course_id')
            ->select('course_id', DB::raw('COUNT(id) as total'))
            ->pluck('total', 'course_id');

        $courseStats = $courseStats->map(function ($course) use (
            $certificatesByCourse,
            $requestsByCourse,
            $approvedRequestsByCourse
        ) {
            $course->enrollments_count = (int) $course->enrollments_count;
            $course->learners_count = (int) $course->learners_count;
            $course->completed_count = (int) $course->completed_count;
            $course->average_progress = (float) ($course->average_progress ?? 0);
            $course->certificates_count = (int) ($certificatesByCourse[$course->id] ?? 0);
            $course->requests_count = (int) ($requestsByCourse[$course->id] ?? 0);
            $course->approved_requests_count = (int) ($approvedRequestsByCourse[$course->id] ?? 0);
            $course->completion_rate = $course->enrollments_count > 0
                ? round(($course->completed_count / $course->enrollments_count) * 100, 2)
                : 0;

            return $course;
        });

        $recentCertificates = DB::table('certificates as cert')
            ->join('enrollments as e', 'e.id', '=', 'cert.enrollment_id')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->leftJoin('accreditations as a', 'a.id', '=', 'cert.accreditation_id')
            ->where('e.company_id', $company->id)
            ->whereNull('cert.deleted_at')
            ->whereNotNull('cert.issued_at')
            ->select([
                'cert.id',
                'cert.name',
                'cert.certificate_number',
                'cert.issued_at',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'c.ref as course_ref',
                'c.title as course_title',
                'a.code as accreditation_code',
                'a.name as accreditation_name',
            ])
            ->orderByDesc('cert.issued_at')
            ->limit(10)
            ->get()
            ->map(function ($certificate) {
                $certificate->learner_name = collect([
                    $certificate->first_name,
                    $certificate->middle_name,
                    $certificate->last_name,
                ])->filter()->implode(' ');

                return $certificate;
            });

        $companyData = [
            'id' => $company->id,
            'hashid' => $hashid,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'status' => $company->status,
            'logo' => $company->logo,
            'logo_url' => $company->logo
                ? Storage::disk('public')->url($company->logo)
                : null,
            'created_at' => $company->created_at,
            'updated_at' => $company->updated_at,
        ];

        $stats = [
            'users_count' => $usersCount,
            'requests_count' => $requestsCount,
            'pending_requests_count' => $pendingRequestsCount,
            'approved_requests_count' => $approvedRequestsCount,
            'rejected_requests_count' => $rejectedRequestsCount,
            'requested_courses_count' => $requestedCoursesCount,
            'approved_courses_count' => $approvedCoursesCount,
            'assigned_courses_count' => $assignedCoursesCount,
            'enrollments_count' => $enrollmentsCount,
            'learners_enrolled_count' => $learnersEnrolledCount,
            'not_started_enrollments_count' => $notStartedEnrollmentsCount,
            'in_progress_enrollments_count' => $inProgressEnrollmentsCount,
            'completed_enrollments_count' => $completedEnrollmentsCount,
            'expired_enrollments_count' => $expiredEnrollmentsCount,
            'average_progress' => $averageProgress,
            'certificates_issued_count' => $certificatesIssuedCount,
            'accreditations_used_count' => $accreditationsUsedCount,
            'exam_attempts_count' => $finishedExamAttempts,
            'passed_exam_attempts_count' => $passedExamAttempts,
            'failed_exam_attempts_count' => $failedExamAttempts,
            'request_approval_rate' => $requestApprovalRate,
            'completion_rate' => $completionRate,
            'exam_pass_rate' => $examPassRate,
        ];

        return Inertia::render('companies/showCompany', [
            'company' => $companyData,
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'courseStats' => $courseStats,
            'recentCertificates' => $recentCertificates,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('companies', 'email')],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        try {
            DB::beginTransaction();

            $logoPath = null;

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('companies', 'public');
            }

            Company::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address'],
                'status' => $validatedData['status'],
                'logo' => $logoPath,
            ]);

            DB::commit();

            return back()->with('success', 'Company added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withErrors([
                'error' => 'An error occurred while adding the company.',
            ]);
        }
    }

    public function update(Request $request, string $hashid)
    {
        $decoded = Hashids::decode($hashid);
        abort_if(empty($decoded), 404);

        $company = Company::findOrFail($decoded[0]);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($company->id),
            ],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            $company->name = $validatedData['name'];
            $company->email = $validatedData['email'];
            $company->phone = $validatedData['phone'];
            $company->address = $validatedData['address'];
            $company->status = $validatedData['status'];

            if ($request->boolean('remove_logo')) {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }

                $company->logo = null;
            }

            if ($request->hasFile('logo')) {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }

                $company->logo = $request->file('logo')->store('companies', 'public');
            }

            $company->save();

            DB::commit();

            return back()->with('success', 'Company updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withErrors([
                'error' => 'An error occurred while updating the company.',
            ]);
        }
    }

    public function destroy(Request $request, string $hashid)
    {
        try {
            DB::beginTransaction();

            $decoded = Hashids::decode($hashid);
            abort_if(empty($decoded), 404);

            $company = Company::findOrFail($decoded[0]);
            $company->delete();

            DB::commit();

            return back()->with([
                'success' => 'Company deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withErrors([
                'error' => 'An error occurred while deleting the company.',
            ]);
        }
    }
}
