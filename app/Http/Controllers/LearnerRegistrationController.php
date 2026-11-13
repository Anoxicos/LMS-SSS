<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class LearnerRegistrationController extends Controller
{
    /**
     * Afficher le formulaire public de création du compte Learner.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(
                'public.individual-requests.create'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Conserver la destination après la création du compte
        |--------------------------------------------------------------------------
        */
        $request->session()->put(
            'url.intended',
            route('public.individual-requests.create')
        );

        return Inertia::render(
            'Public/Auth/LearnerRegister',
            [
                'loginUrl' => route('login'),
            ]
        );
    }

    /**
     * Créer directement un compte Learner sans SMTP.
     *
     * Aucun événement Registered n'est déclenché et aucun e-mail de
     * vérification n'est envoyé. Le compte est considéré comme vérifié,
     * activé et connecté immédiatement.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'first_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'middle_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'last_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email'),
                ],

                'country' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'date_of_birth' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'place_of_birth' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'passport_number' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('users', 'passport_number'),
                ],

                'passport_file' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'max:5120',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],

                'terms' => [
                    'accepted',
                ],
            ],
            [
                'passport_file.required' =>
                    'The passport document is required.',

                'passport_file.mimes' =>
                    'The passport document must be a PDF, JPG, JPEG, PNG or WEBP file.',

                'passport_file.max' =>
                    'The passport document must not exceed 5 MB.',

                'terms.accepted' =>
                    'You must accept the terms before creating your account.',
            ]
        );

        $passportPath = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | Stockage privé du passeport
            |--------------------------------------------------------------------------
            */

            $passportPath = $request
                ->file('passport_file')
                ->store(
                    'learner-passports',
                    'local'
                );

            $user = DB::transaction(
                function () use (
                    $validated,
                    $passportPath
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Récupération ou création du rôle Learner
                    |--------------------------------------------------------------------------
                    */

                    $learnerRole = Role::query()
                        ->whereRaw(
                            'LOWER(name) = ?',
                            ['learner']
                        )
                        ->first();

                    if (!$learnerRole) {
                        $learnerRole = Role::create([
                            'name' => 'Learner',
                            'guard_name' => 'web',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Création du compte Learner
                    |--------------------------------------------------------------------------
                    */

                    $user = User::create([
                        'first_name' =>
                            trim($validated['first_name']),

                        'middle_name' =>
                            filled(
                                $validated['middle_name']
                                ?? null
                            )
                                ? trim(
                                    $validated['middle_name']
                                )
                                : null,

                        'last_name' =>
                            trim($validated['last_name']),

                        /*
                        | Learner individuel sans société.
                        */
                        'company_id' => null,

                        'passport_number' =>
                            trim(
                                $validated['passport_number']
                            ),

                        'passport_file' =>
                            $passportPath,

                        'email' =>
                            mb_strtolower(
                                trim($validated['email'])
                            ),

                        'password' =>
                            Hash::make(
                                $validated['password']
                            ),

                        /*
                        | Le compte est vérifié directement.
                        | Aucun e-mail SMTP n'est envoyé.
                        */
                        'email_verified_at' => now(),

                        'status' => 'active',

                        'country' =>
                            trim($validated['country']),

                        'date_of_birth' =>
                            $validated['date_of_birth'],

                        'place_of_birth' =>
                            trim(
                                $validated['place_of_birth']
                            ),
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Attribution du rôle Learner
                    |--------------------------------------------------------------------------
                    */

                    $user->syncRoles([
                        $learnerRole,
                    ]);

                    return $user;
                }
            );
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Suppression du fichier en cas d'échec
            |--------------------------------------------------------------------------
            */

            if ($passportPath) {
                Storage::disk('local')->delete(
                    $passportPath
                );
            }

            report($exception);

            return back()
                ->withErrors([
                    'registration' =>
                        'The learner account could not be created.',
                ])
                ->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('courses')
            ->with([
                'arrival_intro' => true,
                'success' =>
                    'Your learner account has been created successfully.',
            ]);
    }
}
