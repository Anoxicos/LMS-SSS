<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationCodeController extends Controller
{
    public function verify(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^[0-9]{6}$/',
            ],
        ], [
            'code.required' =>
                'Please enter your verification code.',

            'code.regex' =>
                'The verification code must contain exactly 6 digits.',
        ]);

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Déjà vérifié
        |--------------------------------------------------------------------------
        */

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(
                config(
                    'fortify.home',
                    '/'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aucun code
        |--------------------------------------------------------------------------
        */

        if (
            !$user->email_verification_code ||
            !$user->email_verification_code_expires_at
        ) {
            throw ValidationException::withMessages([
                'code' =>
                    'No active verification code was found. Please request a new code.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Code expiré
        |--------------------------------------------------------------------------
        */

        if (
            now()->greaterThan(
                $user->email_verification_code_expires_at
            )
        ) {
            $user->clearEmailVerificationCode();

            throw ValidationException::withMessages([
                'code' =>
                    'This verification code has expired. Please request a new code.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Vérification du code
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $validated['code'],
                $user->email_verification_code
            )
        ) {
            throw ValidationException::withMessages([
                'code' =>
                    'The verification code is incorrect.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validation définitive de l'email
        |--------------------------------------------------------------------------
        */

        if (
            $user->markEmailAsVerified()
        ) {
            event(
                new Verified($user)
            );
        }

        /*
        | Le code devient inutilisable.
        */
        $user->clearEmailVerificationCode();

        /*
        |--------------------------------------------------------------------------
        | Redirection
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->intended(
                config(
                    'fortify.home',
                    '/'
                )
            )
            ->with(
                'status',
                'email-verified'
            );
    }
}