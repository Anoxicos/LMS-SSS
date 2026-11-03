<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

        // Diffusion du certificat au learner
        'certificate_visible_to_learner',

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

        // Important : retourne toujours true/false
        'certificate_visible_to_learner' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by_id'
        );
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'validated_by_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'company_request_user'
        )
            ->withPivot('status')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(
            Enrollment::class,
            'company_request_id'
        );
    }

    public function course(): BelongsTo
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

    /*
    |--------------------------------------------------------------------------
    | Accreditation helpers
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Validity helpers
    |--------------------------------------------------------------------------
    */

    public function hasLimitedValidity(): bool
    {
        return $this->duree_validite_formation !== null
            && (int) $this->duree_validite_formation > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Certificate helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Détermine si les certificats de cette demande
     * doivent être diffusés aux learners.
     */
    public function canLearnersAccessCertificates(): bool
    {
        return (bool) $this->certificate_visible_to_learner;
    }

    /**
     * Synchronise la visibilité du certificat
     * sur tous les enrollments liés à cette demande.
     */
    public function syncCertificateVisibilityToEnrollments(): void
    {
        $this->enrollments()->update([
            'certificate_visible_to_learner' =>
                (bool) $this->certificate_visible_to_learner,
        ]);
    }
}