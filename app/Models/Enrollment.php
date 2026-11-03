<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Vinkla\Hashids\Facades\Hashids;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'company_id',
        'company_request_id',
        'current_chapter_id',
        'access_type',
        'status',
        'started_at',
        'completed_at',
        'expired_at',
        'progress_percent',
        'comment',
        'is_approved',
        'attempts_rest',
        'duree_validite_formation',
        'individual_request_course_id',

        // Diffusion du certificat au learner
        'certificate_visible_to_learner',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',

        'progress_percent' => 'decimal:2',

        'is_approved' => 'boolean',

        'attempts_rest' => 'integer',
        'duree_validite_formation' => 'integer',

        'certificate_visible_to_learner' => 'boolean',
    ];

    protected $appends = [
        'hashid',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyRequest(): BelongsTo
    {
        return $this->belongsTo(CompanyRequest::class);
    }

    public function currentChapter(): BelongsTo
    {
        return $this->belongsTo(
            Chapter::class,
            'current_chapter_id'
        );
    }

    public function slideProgress(): HasMany
    {
        return $this->hasMany(SlideProgress::class);
    }

    public function chapterProgress(): HasMany
    {
        return $this->hasMany(ChapterProgress::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function finalExams(): HasMany
    {
        return $this->hasMany(Exam::class)
            ->where('is_final_exam', true);
    }

    public function latestExam(): HasOne
    {
        return $this->hasOne(Exam::class)
            ->latestOfMany();
    }

    public function scormTracking(): HasOne
    {
        return $this->hasOne(ScormTracking::class);
    }

    public function individualRequestCourse(): BelongsTo
    {
        return $this->belongsTo(
            IndividualRequestCourse::class,
            'individual_request_course_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function decodeHashid($hashid)
    {
        $decoded = Hashids::decode($hashid);

        if (empty($decoded)) {
            return null;
        }

        return (int) $decoded[0];
    }

    public static function findByHashid($hashid)
    {
        $id = self::decodeHashid($hashid);

        if (!$id) {
            return null;
        }

        return self::find($id);
    }

    public static function findByHashidOrFail($hashid)
    {
        $id = self::decodeHashid($hashid);

        if (!$id) {
            abort(404);
        }

        return self::findOrFail($id);
    }

    public function isScormCompleted(): bool
    {
        return (bool) $this->scormTracking?->is_completed;
    }

    public function canStartFinalExam(): bool
    {
        return $this->isScormCompleted()
            && (int) $this->attempts_rest > 0;
    }

    public function hasLimitedValidity(): bool
    {
        return $this->duree_validite_formation !== null
            && (int) $this->duree_validite_formation > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Access type helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Enrollment provenant d'une demande entreprise.
     */
    public function isCompanyEnrollment(): bool
    {
        return $this->company_request_id !== null
            || $this->access_type === 'company';
    }

    /**
     * Enrollment individuel.
     */
    public function isIndividualEnrollment(): bool
    {
        return $this->individual_request_course_id !== null
            || $this->access_type === 'individual';
    }

    /*
    |--------------------------------------------------------------------------
    | Certificate visibility
    |--------------------------------------------------------------------------
    */

    /**
     * Détermine si le learner a le droit de voir/télécharger
     * ses certificats.
     *
     * Règles :
     *
     * - Individual :
     *   certificat toujours diffusé.
     *
     * - Company :
     *   dépend de certificate_visible_to_learner.
     */
    public function canLearnerAccessCertificate(): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Individual enrollment
        |--------------------------------------------------------------------------
        |
        | Un learner individuel doit toujours avoir accès
        | à son certificat.
        |
        */
        if ($this->isIndividualEnrollment()) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Company enrollment
        |--------------------------------------------------------------------------
        |
        | Pour une inscription entreprise, on respecte
        | le choix enregistré dans l'enrollment.
        |
        */
        if ($this->isCompanyEnrollment()) {
            return (bool) $this->certificate_visible_to_learner;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        |
        | Pour les anciens enrollments ou un autre type d'accès,
        | on conserve le comportement historique.
        |
        */
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Certificates
    |--------------------------------------------------------------------------
    */

    /**
     * Ancienne relation.
     *
     * Attention :
     * un enrollment peut maintenant posséder plusieurs certificats
     * selon les accréditations.
     */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * Tous les certificats liés à l'enrollment.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Certificat EASA.
     */
    public function easaCertificate(): HasOne
    {
        return $this->hasOne(Certificate::class)
            ->whereHas('accreditation', function ($query) {
                $query->where('code', 'easa');
            });
    }

    /**
     * Certificat DAC.
     */
    public function dacCertificate(): HasOne
    {
        return $this->hasOne(Certificate::class)
            ->whereHas('accreditation', function ($query) {
                $query->where('code', 'dac');
            });
    }
}