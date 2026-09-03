<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',
        'progress_percent' => 'decimal:2',
        'is_approved' => 'boolean',
        'attempts_rest' => 'integer',
        'duree_validite_formation' => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyRequest()
    {
        return $this->belongsTo(CompanyRequest::class);
    }

    public function currentChapter()
    {
        return $this->belongsTo(Chapter::class, 'current_chapter_id');
    }

    public function slideProgress()
    {
        return $this->hasMany(SlideProgress::class);
    }

    public function chapterProgress()
    {
        return $this->hasMany(ChapterProgress::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function finalExams()
    {
        return $this->hasMany(Exam::class)
            ->where('is_final_exam', true);
    }

    public function latestExam()
    {
        return $this->hasOne(Exam::class)->latestOfMany();
    }

    public function scormTracking()
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
    | Certificates
    |--------------------------------------------------------------------------
    */

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function easaCertificate(): HasOne
    {
        return $this->hasOne(Certificate::class)
            ->whereHas('accreditation', function ($query) {
                $query->where('code', 'easa');
            });
    }

    public function dacCertificate(): HasOne
    {
        return $this->hasOne(Certificate::class)
            ->whereHas('accreditation', function ($query) {
                $query->where('code', 'dac');
            });
    }
}
