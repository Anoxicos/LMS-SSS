<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScormTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'scorm_version',

        'lesson_status',
        'completion_status',
        'success_status',

        'progress_measure',
        'score_raw',
        'score_min',
        'score_max',
        'score_scaled',

        'lesson_location',
        'suspend_data',

        'session_time',
        'total_time',
        'exit_status',

        'cmi_data',

        'is_completed',
        'completed_at',
        'last_committed_at',
    ];

    protected $casts = [
        'cmi_data' => 'array',

        'progress_measure' => 'decimal:6',
        'score_raw' => 'decimal:2',
        'score_min' => 'decimal:2',
        'score_max' => 'decimal:2',
        'score_scaled' => 'decimal:6',

        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_committed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}