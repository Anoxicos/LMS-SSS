<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndividualRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'reference',
        'user_id',
        'status',
        'learner_note',
        'admin_note',
        'reviewed_by_id',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function reviewedBy()
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            IndividualRequestCourse::class,
            'individual_request_id'
        );
    }
}