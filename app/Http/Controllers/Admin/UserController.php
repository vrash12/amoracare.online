<?php

// laravel-app/app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\UserAccountStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can access user management.');
        }
    }

    public function index(
        Request $request,
        UserAccountStatusService $accountStatusService
    ): View {
        $this->authorizeAdmin();

        $automaticallyInactivated = $accountStatusService->deactivateDormantUsers();

        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');

        $users = User::with('role')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->when($role, fn ($query) => $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', $role)))
            ->when(in_array($status, ['active', 'inactive', 'pending'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        $statusCounts = User::query()
            ->whereIn('status', [
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
                User::STATUS_PENDING,
            ])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.users.index', compact(
            'users',
            'search',
            'role',
            'status',
            'roles',
            'statusCounts',
            'automaticallyInactivated'
        ))->with('inactivityDays', $accountStatusService->inactivityDays());
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        User::create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorizeAdmin();

        $user->load('role');

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($user->id === Auth::id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account deleted successfully.');
    }
}
