<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlideQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'slide_id',
        'question',
        'question_type',
        'option_a',
        'option_b',
        'option_c',
        'correct_answer',
        'explanation',
        'sort_order',
    ];

    public function slide()
    {
        return $this->belongsTo(Slide::class);
    }
}