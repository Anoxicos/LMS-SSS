<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Certificate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'accreditation_id',
        'name',
        'issuer_name',
        'approval_reference',
        'certificate_number',
        'file_path',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    protected $appends = [
        'hashid',
    ];

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->getKey());
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function accreditation(): BelongsTo
    {
        return $this->belongsTo(Accreditation::class);
    }
}
