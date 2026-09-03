<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'reference',
        'status',
        'start_date',
        'end_date',
        'duree_validite_formation',
        'requested_by_id',
        'validated_by_id',
        'validated_at',
        'notes',
        'course_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'validated_at' => 'datetime',
        'duree_validite_formation' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_request_user')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function accreditations(): BelongsToMany
    {
        return $this->belongsToMany(
            Accreditation::class,
            'accreditation_company_request'
        )->withTimestamps();
    }

    public function hasEasaAccreditation(): bool
    {
        return $this->accreditations
            ->contains('code', 'easa');
    }

    public function hasDacAccreditation(): bool
    {
        return $this->accreditations
            ->contains('code', 'dac');
    }

    public function hasDoubleAccreditation(): bool
    {
        return $this->hasEasaAccreditation()
            && $this->hasDacAccreditation();
    }

    public function hasLimitedValidity(): bool
    {
        return $this->duree_validite_formation !== null
            && (int) $this->duree_validite_formation > 0;
    }
}
