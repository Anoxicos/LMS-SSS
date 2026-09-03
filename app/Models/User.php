<?php

namespace App\Models;

use App\Services\MicrosoftOAuthMailer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'company_id',
        'passport_number',
        'passport_file',
        'email',
        'password',
        'status',
        'country',
        'date_of_birth',
        'place_of_birth',
        'profile_photo_path',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',

        /*
        | Ne jamais envoyer le hash
        | du code au frontend.
        */
        'email_verification_code',
        'email_verification_code_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',

        'email_verification_code_expires_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
        'hashid',
        'name',
        'full_name',
        'formatted_date_of_birth',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])
            ->filter()
            ->implode(' ');
    }

    public function getFormattedDateOfBirthAttribute(): ?string
    {
        return $this->date_of_birth?->format('d-m-Y');
    }

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */

    public function sendEmailVerificationNotification(): void
    {
        /*
        | Ne pas renvoyer de code
        | si l'adresse est déjà validée.
        */
        if ($this->hasVerifiedEmail()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Générer un code sécurisé à 6 chiffres
        |--------------------------------------------------------------------------
        */

        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        /*
        |--------------------------------------------------------------------------
        | Stocker uniquement le HASH
        |--------------------------------------------------------------------------
        |
        | Le code réel ne doit jamais être enregistré en clair.
        |
        */

        $this->forceFill([
            'email_verification_code' => Hash::make(
                $code
            ),

            'email_verification_code_expires_at' =>
                now()->addMinutes(10),
        ])->saveQuietly();

        try {
            app(
                MicrosoftOAuthMailer::class
            )->sendVerificationCode(
                $this,
                $code
            );
        } catch (\Throwable $exception) {
            /*
            | Si l'envoi échoue, le code
            | généré est immédiatement invalidé.
            */

            $this->clearEmailVerificationCode();

            throw $exception;
        }
    }

    public function clearEmailVerificationCode(): void
    {
        $this->forceFill([
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->saveQuietly();
    }


    /*
    |--------------------------------------------------------------------------
    | Reset Password
    |--------------------------------------------------------------------------
    */
    

    public function sendPasswordResetNotification($token): void 
    {
        app(
            MicrosoftOAuthMailer::class
        )->sendPasswordResetLink(
            $this,
            $token
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function requestedCompanyRequests()
    {
        return $this->hasMany(
            CompanyRequest::class,
            'requested_by_id'
        );
    }

    public function validatedCompanyRequests()
    {
        return $this->hasMany(
            CompanyRequest::class,
            'validated_by_id'
        );
    }

    public function companyRequests()
    {
        return $this->belongsToMany(
            CompanyRequest::class,
            'company_request_user'
        )
            ->withPivot('status')
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(
            Enrollment::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Individual
    |--------------------------------------------------------------------------
    */

    public function individualRequests(): HasMany
    {
        return $this->hasMany(
            IndividualRequest::class
        );
    }

    public function reviewedIndividualRequests(): HasMany
    {
        return $this->hasMany(
            IndividualRequest::class,
            'reviewed_by_id'
        );
    }
}