<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ParentMatchingProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ]);

        $parent = DB::transaction(function () use ($validated, $request): User {
            $role = Role::firstOrCreate(
                ['slug' => 'prospective_parent'],
                [
                    'name' => 'Prospective Adoptive Parent',
                    'description' => 'Applicant for adoption assistance, case monitoring, and matching review.',
                ]
            );

            $parent = User::create([
                'role_id' => $role->id,
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'phone_number' => $validated['phone_number'],
                'status' => User::STATUS_PENDING,
                'password' => Hash::make($validated['password']),
            ]);

            ParentMatchingProfile::create([
                'user_id' => $parent->id,
                'preferred_child_sex' => $validated['preferred_child_sex'],
                'min_child_age' => $validated['min_child_age'],
                'max_child_age' => $validated['max_child_age'],
                'open_to_special_needs' => $request->boolean('open_to_special_needs'),
                'home_study_verified' => false,
                'financial_capacity_score' => 0,
                'housing_score' => 0,
                'parenting_capacity_score' => 0,
                'matching_notes' => null,
            ]);

            return $parent;
        });

        $request->session()->put([
            'email_verification_user_id' => $parent->id,
            'email_verification_remember' => false,
            'email_verification_purpose' => 'registration',
        ]);

        try {
            $sent = $emailVerificationService->sendCode($parent, 'registration');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('email.verification.notice')
                ->withErrors(['code' => $exception->getMessage()]);
        }

        return redirect()
            ->route('email.verification.notice')
            ->with(
                'success',
                $sent
                    ? 'A sign-up OTP was sent to your email address.'
                    : 'A sign-up OTP was sent recently. Check your inbox or wait before requesting another.'
            );
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
