<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChapterProgress extends Model
{
    use HasFactory;

    protected $table = 'chapter_progress';

    protected $fillable = [
        'enrollment_id',
        'chapter_id',
        'last_slide_id',
        'status',
        'started_at',
        'is_completed',
        'progress_percent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'is_completed' => 'boolean',
        'progress_percent' => 'decimal:2',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lastSlide()
    {
        return $this->belongsTo(Slide::class, 'last_slide_id');
    }

    
}