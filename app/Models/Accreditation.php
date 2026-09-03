<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accreditation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'organization_name',
        'authority_name',
        'country',
        'approval_reference',
        'logo_path',
        'certificate_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function companyRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyRequest::class,
            'accreditation_company_request'
        )->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}