<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * @var list<string>
     */
    private array $availablePermissions = [
        'dashboard',
        'smart_scan',
        'documentation',
        'ai_consumption',
    ];

    public function index(Request $request): Response
    {
        $this->authorize('manage-admins');

        $users = User::query()
            ->where('role', 'admin')
            ->latest()
            ->get(['id', 'name', 'email', 'role', 'permissions', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->permissions ?? [],
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('superadmin/user-management', [
            'users' => $users,
            'availablePermissions' => $this->availablePermissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-admins');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($this->availablePermissions)],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'permissions' => array_values(array_unique($validated['permissions'])),
        ]);

        return redirect()->route('superadmin.users.index')->with('success', 'Admin account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-admins');

        if ($user->role !== 'admin') {
            abort(422, 'Only admin accounts can be updated here.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($this->availablePermissions)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->permissions = array_values(array_unique($validated['permissions']));

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('superadmin.users.index')->with('success', 'Admin account updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-admins');

        if ($user->id === $request->user()?->id) {
            return redirect()->route('superadmin.users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->role !== 'admin') {
            abort(422, 'Only admin accounts can be deleted here.');
        }

        $user->delete();

        return redirect()->route('superadmin.users.index')->with('success', 'Admin account deleted successfully.');
    }
}
