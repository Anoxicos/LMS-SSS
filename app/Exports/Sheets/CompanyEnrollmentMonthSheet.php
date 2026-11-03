<?php

namespace App\Exports\Sheets;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompanyEnrollmentMonthSheet implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    /*
    |--------------------------------------------------------------------------
    | Colonnes visibles dans Excel
    |--------------------------------------------------------------------------
    |
    | C'est la seule variable à modifier pour afficher/masquer les colonnes.
    |
    */
    private array $visibleColumns = [
        'enrollment_date',
        'learner',
        'email',
        'course',
        'status',
        'progress',
        'exam_passed',
        'certificate',
        'certificate_numbers',
        'accreditations',
    ];

    /*
    |--------------------------------------------------------------------------
    | Labels des colonnes
    |--------------------------------------------------------------------------
    */
    private array $columnLabels = [
        'enrollment_id' => 'Enrollment ID',
        'enrollment_date' => 'Enrollment Date',

        'learner' => 'Learner',
        'email' => 'Email',
        'passport' => 'Passport',
        'country' => 'Country',

        'course_ref' => 'Course Ref',
        'course' => 'Course',
        'content_type' => 'Content Type',
        'training_type' => 'Training Type',

        'access_type' => 'Access Type',
        'status' => 'Status',
        'progress' => 'Progress %',

        'started_at' => 'Started At',
        'completed_at' => 'Completed At',
        'expired_at' => 'Expired At',

        'validity_days' => 'Validity Days',
        'attempts_remaining' => 'Attempts Remaining',

        'exam_attempts' => 'Exam Attempts',
        'best_exam_score' => 'Best Exam Score',
        'exam_passed' => 'Exam Passed',

        'certificate' => 'Certificate',
        'certificates_count' => 'Certificates Count',
        'certificate_numbers' => 'Certificate Numbers',
        'accreditations' => 'Accreditations',
        'certificate_issued_at' => 'Certificate Issued At',

        'company_request' => 'Company Request',
    ];

    public function __construct(
        private Company $company,
        private Carbon $startDate,
        private Carbon $endDate,
        private Carbon $month
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Nom de la feuille
    |--------------------------------------------------------------------------
    */
    public function title(): string
    {
        return ucfirst(
            $this->month
                ->locale('fr')
                ->translatedFormat('F Y')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | En-têtes Excel
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        return collect($this->visibleColumns)
            ->map(
                fn ($column) =>
                    $this->columnLabels[$column] ?? $column
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Données
    |--------------------------------------------------------------------------
    */
    public function collection(): Collection
    {
        /*
        |--------------------------------------------------------------------------
        | Statistiques examens
        |--------------------------------------------------------------------------
        */
        $examStats = DB::table('exams')
            ->whereNull('deleted_at')
            ->selectRaw('
                enrollment_id,
                COUNT(id) as exam_attempts,
                MAX(score) as best_exam_score,
                MAX(
                    CASE
                        WHEN passed = 1 THEN 1
                        ELSE 0
                    END
                ) as exam_passed
            ')
            ->groupBy('enrollment_id');

        /*
        |--------------------------------------------------------------------------
        | Statistiques certificats
        |--------------------------------------------------------------------------
        */
        $certificateStats = DB::table('certificates as cert')
            ->leftJoin(
                'accreditations as acc',
                'acc.id',
                '=',
                'cert.accreditation_id'
            )
            ->whereNull('cert.deleted_at')
            ->selectRaw("
                cert.enrollment_id,

                COUNT(cert.id) as certificates_count,

                GROUP_CONCAT(
                    DISTINCT cert.certificate_number
                    ORDER BY cert.id
                    SEPARATOR ' | '
                ) as certificate_numbers,

                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        COALESCE(acc.code, ''),
                        CASE
                            WHEN acc.name IS NOT NULL
                            THEN CONCAT(' - ', acc.name)
                            ELSE ''
                        END
                    )
                    ORDER BY acc.id
                    SEPARATOR ' | '
                ) as accreditations,

                GROUP_CONCAT(
                    DISTINCT DATE_FORMAT(
                        cert.issued_at,
                        '%Y-%m-%d'
                    )
                    ORDER BY cert.issued_at
                    SEPARATOR ' | '
                ) as certificate_dates
            ")
            ->groupBy('cert.enrollment_id');

        /*
        |--------------------------------------------------------------------------
        | Enrollments
        |--------------------------------------------------------------------------
        */
        return DB::table('enrollments as e')
            ->join(
                'users as u',
                'u.id',
                '=',
                'e.user_id'
            )
            ->join(
                'courses as c',
                'c.id',
                '=',
                'e.course_id'
            )
            ->leftJoin(
                'company_requests as cr',
                'cr.id',
                '=',
                'e.company_request_id'
            )
            ->leftJoinSub(
                $examStats,
                'exam_stats',
                function ($join) {
                    $join->on(
                        'exam_stats.enrollment_id',
                        '=',
                        'e.id'
                    );
                }
            )
            ->leftJoinSub(
                $certificateStats,
                'certificate_stats',
                function ($join) {
                    $join->on(
                        'certificate_stats.enrollment_id',
                        '=',
                        'e.id'
                    );
                }
            )
            ->where(
                'e.company_id',
                $this->company->id
            )
            ->whereBetween(
                'e.created_at',
                [
                    $this->startDate,
                    $this->endDate,
                ]
            )
            ->orderBy('e.created_at')
            ->orderBy('u.last_name')
            ->orderBy('u.first_name')
            ->select([
                'e.id',
                'e.created_at',

                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.email',
                'u.passport_number',
                'u.country',

                'c.ref as course_ref',
                'c.title as course_title',
                'c.content_type',
                'c.training_type',

                'e.access_type',
                'e.status',
                'e.progress_percent',

                'e.started_at',
                'e.completed_at',
                'e.expired_at',

                'e.duree_validite_formation',
                'e.attempts_rest',

                'exam_stats.exam_attempts',
                'exam_stats.best_exam_score',
                'exam_stats.exam_passed',

                'certificate_stats.certificates_count',
                'certificate_stats.certificate_numbers',
                'certificate_stats.accreditations',
                'certificate_stats.certificate_dates',

                'cr.reference as company_request_reference',
            ])
            ->get()
            ->map(function ($row) {

                $fullName = collect([
                    $row->first_name,
                    $row->middle_name,
                    $row->last_name,
                ])
                    ->filter()
                    ->implode(' ');

                $hasCertificate =
                    (int) ($row->certificates_count ?? 0) > 0;

                /*
                |--------------------------------------------------------------------------
                | Toutes les colonnes disponibles
                |--------------------------------------------------------------------------
                */
                $data = [
                    'enrollment_id' =>
                        $row->id,

                    'enrollment_date' =>
                        $this->formatDateTime(
                            $row->created_at
                        ),

                    'learner' =>
                        $fullName ?: '-',

                    'email' =>
                        $row->email ?: '-',

                    'passport' =>
                        $row->passport_number ?: '-',

                    'country' =>
                        $row->country ?: '-',

                    'course_ref' =>
                        $row->course_ref ?: '-',

                    'course' =>
                        $row->course_title ?: '-',

                    'content_type' =>
                        $row->content_type ?: '-',

                    'training_type' =>
                        $row->training_type ?: '-',

                    'access_type' =>
                        $row->access_type ?: '-',

                    'status' =>
                        $row->status ?: '-',

                    'progress' =>
                        (float) (
                            $row->progress_percent ?? 0
                        ),

                    'started_at' =>
                        $this->formatDateTime(
                            $row->started_at
                        ),

                    'completed_at' =>
                        $this->formatDateTime(
                            $row->completed_at
                        ),

                    'expired_at' =>
                        $this->formatDateTime(
                            $row->expired_at
                        ),

                    'validity_days' =>
                        $row->duree_validite_formation
                            ?? '-',

                    'attempts_remaining' =>
                        $row->attempts_rest
                            ?? '-',

                    'exam_attempts' =>
                        (int) (
                            $row->exam_attempts ?? 0
                        ),

                    'best_exam_score' =>
                        $row->best_exam_score !== null
                            ? (float) $row->best_exam_score
                            : '-',

                    'exam_passed' =>
                        $row->exam_attempts
                            ? (
                                $row->exam_passed
                                    ? 'Yes'
                                    : 'No'
                            )
                            : '-',

                    'certificate' =>
                        $hasCertificate
                            ? 'Yes'
                            : 'No',

                    'certificates_count' =>
                        (int) (
                            $row->certificates_count ?? 0
                        ),

                    'certificate_numbers' =>
                        $row->certificate_numbers
                            ?: '-',

                    'accreditations' =>
                        $row->accreditations
                            ?: '-',

                    'certificate_issued_at' =>
                        $row->certificate_dates
                            ?: '-',

                    'company_request' =>
                        $row->company_request_reference
                            ?: '-',
                ];

                /*
                |--------------------------------------------------------------------------
                | Retourne seulement les colonnes visibles
                |--------------------------------------------------------------------------
                */
                return collect(
                    $this->visibleColumns
                )
                    ->map(
                        fn ($column) =>
                            $data[$column] ?? '-'
                    )
                    ->values()
                    ->all();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Format date
    |--------------------------------------------------------------------------
    */
    private function formatDateTime(
        $value
    ): string {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value)
            ->format('Y-m-d H:i');
    }

    /*
    |--------------------------------------------------------------------------
    | Style Excel
    |--------------------------------------------------------------------------
    */
    public function styles(
        Worksheet $sheet
    ): array {
        $sheet
            ->getStyle('1:1')
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setRGB('FFFFFF');

        $sheet
            ->getStyle('1:1')
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setRGB('0D8FE9');

        $sheet->freezePane('A2');

        $highestColumn =
            $sheet->getHighestColumn();

        $sheet->setAutoFilter(
            "A1:{$highestColumn}1"
        );

        $sheet
            ->getRowDimension(1)
            ->setRowHeight(24);

        return [];
    }
}