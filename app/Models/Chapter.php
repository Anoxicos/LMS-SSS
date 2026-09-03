<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function slides()
    {
        return $this->hasMany(Slide::class)->orderBy('sort_order');
    }

    public function enrollmentsAsCurrentChapter()
    {
        return $this->hasMany(Enrollment::class, 'current_chapter_id');
    }

    public function chapterProgress()
    {
        return $this->hasMany(ChapterProgress::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    
}