<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'description',
        'type',
        'training_type',
        'skill_level',
        'course_focus',
        'language',
        'price',
        'currency',
        'is_published',
        'image',

        'ref',
        'method',
        'duration_hours',
        'passing_score',
        'syllabus',

        // Global Voice Over settings
        'support_voice_over',
        'voice_over_auto_play',
        'voice_over_provider',
        'voice_over_lang',
        'voice_over_voice',

        // scorm
        'content_type',

        'scorm_zip_path',
        'scorm_extract_path',
        'scorm_entry_file',
        'scorm_version',
        'scorm_identifier',
        'scorm_title',

       
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
        'support_voice_over' => 'boolean',
        'voice_over_auto_play' => 'boolean',
        
        'duration_hours' => 'decimal:2',
        'passing_score' => 'decimal:2',
    ];

   

    protected $appends = [
        'hashid',
        'image_url',
        'voice_over_label',
        'voice_over_auto_play_label',
        'scorm_launch_url'
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

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }

    public function getVoiceOverLabelAttribute()
    {
        return $this->support_voice_over ? 'Supported' : 'Not Supported';
    }

    public function getVoiceOverAutoPlayLabelAttribute()
    {
        return $this->voice_over_auto_play ? 'Enabled' : 'Disabled';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tag')
            ->withTimestamps();
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)
            ->orderBy('sort_order');
    }

    public function slides()
    {
        return $this->hasManyThrough(
            Slide::class,
            Chapter::class,
            'course_id',
            'chapter_id',
            'id',
            'id'
        );
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function plan()
    {
        return $this->hasOne(Plan::class);
    }

    public function banks()
    {
        return $this->hasMany(Bank::class);
    }

    

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function supportsVoiceOver()
    {
        return (bool) $this->support_voice_over;
    }

    public function hasVoiceOverAutoPlay()
    {
        return (bool) $this->voice_over_auto_play;
    }

    public function getVoiceProvider()
    {
        return $this->voice_over_provider ?: 'voicerss';
    }

    public function getVoiceLanguage()
    {
        return $this->voice_over_lang ?: 'en-us';
    }

    public function getVoiceName()
    {
        return $this->voice_over_voice ?: 'default';
    }


    public function companyRequests()
    {
        return $this->hasMany(CompanyRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scorm
    |--------------------------------------------------------------------------
    */

    public function getScormLaunchUrlAttribute()
    {
        if (!$this->scorm_extract_path || !$this->scorm_entry_file) {
            return null;
        }

        return asset('storage/' . $this->scorm_extract_path . '/' . $this->scorm_entry_file);
    }

    public function isNormal()
    {
        return $this->content_type === 'normal';
    }

    public function isScorm()
    {
        return $this->content_type === 'scorm';
    }



    /*
    |--------------------------------------------------------------------------
    | ndividual
    |--------------------------------------------------------------------------
    */


    
    public function individualRequestItems(): HasMany
    {
        return $this->hasMany(
            IndividualRequestCourse::class
        );
    }

    
}