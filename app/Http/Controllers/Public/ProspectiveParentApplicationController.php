<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProspectiveParentApplicationController extends Controller
{
    public function create(): View
    {
        return view('public.prospective-parent-application');
    }

    public function store(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'preferred_child_sex' => ['required', Rule::in(['any', 'male', 'female'])],
            'min_child_age' => ['required', 'integer', 'min:0', 'max:18'],
            'max_child_age' => ['required', 'integer', 'min:0', 'max:18', 'gte:min_child_age'],
            'open_to_special_needs' => ['nullable', 'boolean'],
            'consent' => ['accepted'],
            'terms_accepted' => ['accepted'],
        ]);

        $email = strtolower($validated['email']);
        $normalizedName = (new User(['name' => $validated['name']]))->name;

        try {
            $challenge = $emailVerificationService->createPendingChallenge(
                $normalizedName,
                $email,
                'registration'
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        $request->session()->forget([
            'email_verification_user_id',
            'email_verification_remember',
            'email_verification_guard',
        ]);

        $request->session()->put([
            'email_verification_purpose' => 'registration',
            'pending_parent_registration' => [
                'data' => [
                    'name' => $normalizedName,
                    'email' => $email,
                    'phone_number' => $validated['phone_number'],
                    'password_hash' => Hash::make($validated['password']),
                    'preferred_child_sex' => $validated['preferred_child_sex'],
                    'min_child_age' => (int) $validated['min_child_age'],
                    'max_child_age' => (int) $validated['max_child_age'],
                    'open_to_special_needs' => $request->boolean('open_to_special_needs'),
                    'consented_at' => now()->toIso8601String(),
                    'terms_version' => (string) config('legal.terms_version'),
                ],
                'challenge' => $challenge,
            ],
        ]);

        return redirect()
            ->route('email.verification.notice')
            ->with('success', 'A sign-up OTP was sent to your email address. Your account will only be created after successful verification.');
    }

    public function submitted(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('application_email')) {
            return redirect()->route('parent.application.create');
        }

        return view('public.prospective-parent-application-submitted', [
            'email' => $request->session()->get('application_email'),
        ]);
    }
}
