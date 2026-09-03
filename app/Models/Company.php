<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'status',
        'logo',
    ];

    protected $appends = [
        'hashid',
        'logo_url',
    ];

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return Storage::url($this->logo);
        }
        
        return null;
    }
    

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function companyRequests()
    {
        return $this->hasMany(CompanyRequest::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}