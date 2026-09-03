<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Status constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'enrollment_id',
        'title',
        'description',

        'pass_score',
        'max_attempts',
        'time_limit_minutes',
        'is_final_exam',

        'attempt_number',
        'status',
        'score',
        'passed',

        'total_questions',
        'correct_answers',

        'started_at',
        'submitted_at',
        'duration_seconds',

        'expires_at',
        'submission_type',
        'auto_submitted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'pass_score' => 'decimal:2',
        'score' => 'decimal:2',

        'max_attempts' => 'integer',
        'time_limit_minutes' => 'integer',
        'attempt_number' => 'integer',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'duration_seconds' => 'integer',

        'is_final_exam' => 'boolean',
        'passed' => 'boolean',

        'started_at' => 'datetime',
        'submitted_at' => 'datetime',

        'expires_at' => 'datetime',
        'auto_submitted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'question_exam')
            ->withPivot('response_user', 'is_correct')
            ->withTimestamps();
    }

    public function questionExams()
    {
        return $this->hasMany(QuestionExam::class);
    }
    

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeFinal($query)
    {
        return $query->where('is_final_exam', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isPassed(): bool
    {
        return (bool) $this->passed || $this->status === self::STATUS_PASSED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function markAsSubmitted(float|int|null $score = null, ?int $correctAnswers = null, ?int $totalQuestions = null): void
    {
        $scoreValue = $score ?? $this->score ?? 0;
        $passed = $scoreValue >= (float) $this->pass_score;

        $this->update([
            'score' => $scoreValue,
            'correct_answers' => $correctAnswers ?? $this->correct_answers,
            'total_questions' => $totalQuestions ?? $this->total_questions,
            'passed' => $passed,
            'status' => $passed ? self::STATUS_PASSED : self::STATUS_FAILED,
            'submitted_at' => now(),
            'duration_seconds' => $this->started_at
                ? now()->diffInSeconds($this->started_at)
                : $this->duration_seconds,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'passed' => false,
            'submitted_at' => now(),
            'duration_seconds' => $this->started_at
                ? now()->diffInSeconds($this->started_at)
                : $this->duration_seconds,
        ]);
    }

    public function getScoreLabelAttribute(): string
    {
        if ($this->score === null) {
            return '-';
        }

        return number_format((float) $this->score, 2) . '%';
    }

    public function getDurationLabelAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    
}