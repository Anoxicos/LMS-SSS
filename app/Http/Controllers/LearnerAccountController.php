<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LearnerAccountController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $this->learner($request);

        return Inertia::render('Public/LearnerAccount/Edit', [
            'learner' => $this->formatLearner($user),
            'checkoutUrl' => route('public.individual-requests.create'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->learner($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'passport_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'passport_number')->ignore($user->id),
            ],
            'passport_file' => [
                $user->passport_file ? 'nullable' : 'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $newPassportPath = null;
        $oldPassportPath = $user->passport_file;

        if ($request->hasFile('passport_file')) {
            $newPassportPath = $request
                ->file('passport_file')
                ->store('learner-passports', 'local');
        }

        try {
            DB::transaction(function () use (
                $user,
                $validated,
                $newPassportPath
            ) {
                $user->update([
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => filled($validated['middle_name'] ?? null)
                        ? trim($validated['middle_name'])
                        : null,
                    'last_name' => trim($validated['last_name']),
                    'country' => trim($validated['country']),
                    'date_of_birth' => $validated['date_of_birth'],
                    'place_of_birth' => trim($validated['place_of_birth']),
                    'passport_number' => trim($validated['passport_number']),
                    'passport_file' => $newPassportPath ?: $user->passport_file,
                ]);
            });
        } catch (Throwable $exception) {
            if ($newPassportPath) {
                Storage::disk('local')->delete($newPassportPath);
            }

            report($exception);

            return back()
                ->withErrors([
                    'profile' => 'The learner profile could not be updated.',
                ])
                ->withInput();
        }

        if (
            $newPassportPath &&
            $oldPassportPath &&
            $oldPassportPath !== $newPassportPath
        ) {
            Storage::disk('local')->delete($oldPassportPath);
        }

        return redirect()
            ->route('public.individual-requests.create')
            ->with('success', 'Your learner profile has been updated successfully.');
    }

    private function learner(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyRole(['Learner']),
            403
        );

        return $user;
    }

    private function formatLearner(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'country' => $user->country,
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
            'place_of_birth' => $user->place_of_birth,
            'passport_number' => $user->passport_number,
            'has_passport_file' => filled($user->passport_file),
        ];
    }
}
