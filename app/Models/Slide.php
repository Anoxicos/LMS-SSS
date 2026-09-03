<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slide extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'title',
        'lesson_type',
        'content',
        'video_url',
        'video_duration',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'external_url',
        'is_preview',
        'is_mandatory',
        'is_downloadable',
        'is_active',
        'sort_order',

        'voice_over_enabled',
        'voice_over_text',
        'voice_over_audio_path',
        'voice_over_audio_name',
        'voice_over_audio_type',
        'voice_over_audio_size',
        'voice_over_provider',
        'voice_over_voice',
        'voice_over_lang',
        'voice_over_auto_play',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'video_duration' => 'integer',

        'voice_over_enabled' => 'boolean',
        'voice_over_auto_play' => 'boolean',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function slideProgress()
    {
        return $this->hasMany(SlideProgress::class);
    }

    public function chapterProgressAsLastSlide()
    {
        return $this->hasMany(ChapterProgress::class, 'last_slide_id');
    }

    public function questions()
    {
        return $this->hasMany(SlideQuestion::class)->orderBy('sort_order');
    }
}