<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IndividualRequestCourse extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'individual_request_id',
        'course_id',
        'price_snapshot',
        'currency_snapshot',
        'status',
        'admin_note',
        'start_date',
        'end_date',
        'duree_validite_formation',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function individualRequest(): BelongsTo
    {
        return $this->belongsTo(IndividualRequest::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(
            Enrollment::class,
            'individual_request_course_id'
        );
    }
}
