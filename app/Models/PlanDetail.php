<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'name',
        'nb_question',
        'parent_id',
        'own_questions',
        'total_questions',
    ];

    protected $casts = [
        'own_questions' => 'integer',
        'total_questions' => 'integer',
    ];
    

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function parent()
    {
        return $this->belongsTo(PlanDetail::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PlanDetail::class, 'parent_id');
    }

    
}


