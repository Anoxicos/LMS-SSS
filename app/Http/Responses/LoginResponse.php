<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Utilisateur non vérifié
        |--------------------------------------------------------------------------
        |
        | Le login est réussi, mais email_verified_at = NULL.
        | On génère et envoie immédiatement le code.
        |
        */
        if (
            $user &&
            !$user->hasVerifiedEmail()
        ) {
            $user->sendEmailVerificationNotification();

            /*
            |--------------------------------------------------------------------------
            | Afficher la page de saisie du code
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('verification.notice')
                ->with(
                    'status',
                    'verification-code-sent'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Utilisateur déjà vérifié
        |--------------------------------------------------------------------------
        */

        return redirect()->intended(
            config('fortify.home', '/')
        );
    }
}