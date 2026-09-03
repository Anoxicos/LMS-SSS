<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlideProgress extends Model
{
    use HasFactory;

    protected $table = 'slide_progress';

    protected $fillable = [
        'enrollment_id',
        'slide_id',
        'is_completed',
        'completed_at',
        'is_viewed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'is_viewed' => 'boolean',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function slide()
    {
        return $this->belongsTo(Slide::class);
    }

    
}