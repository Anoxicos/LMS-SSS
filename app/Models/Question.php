<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'name_chapter',
        'question',
        'response1',
        'response2',
        'response3',
        'correct_answer',
        'is_modified',
    ];

    

    protected $casts = [
        'is_modified' => 'boolean',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'question_exam')
            ->withPivot('response_user', 'is_correct')
            ->withTimestamps();
    }

    public function questionExams()
    {
        return $this->hasMany(QuestionExam::class);
    }

    

    
}

