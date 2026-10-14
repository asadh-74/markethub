<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        $search = trim($filters['q'] ?? '');

        return Inertia::render('Admin/Users', [
            'users' => User::query()
                ->with('store:id,user_id,name,status')
                ->withCount('orders')
                ->when($search !== '', fn ($query) => $query->where(
                    fn ($group) => $group->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                ))
                ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'joined_at' => $user->created_at?->format('Y-m-d'),
                    'orders_count' => $user->orders_count,
                    'store' => $user->store?->name,
                ]),
            'filters' => [
                'q' => $search,
                'role' => $filters['role'] ?? '',
            ],
            'current_user_id' => $request->user()->id,
        ]);
    }

    /**
     * Existing admins may grant/revoke admin access to ordinary customer accounts.
     * Vendors cannot change role: their store and order ownership depend on it.
     * Admin actions require the actor's password, and last-admin/self-demotion
     * protections are enforced inside a DB transaction.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Customer->value])],
            'password' => ['required', 'current_password'],
        ]);

        $desired = UserRole::from($data['role']);

        DB::transaction(function () use ($request, $user, $desired) {
            // Lock administrators in ID order so parallel role changes cannot
            // remove all administrators at the same time.
            $admins = User::query()->where('role', UserRole::Admin->value)
                ->orderBy('id')->lockForUpdate()->get();
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            abort_if($target->isVendor() || $target->store()->exists(), 422,
                'Vendor accounts cannot be converted into administrators or customers.');
            abort_if($target->id === $request->user()->id && $desired !== UserRole::Admin, 422,
                'You cannot revoke your own administrator access.');
            abort_if($target->isAdmin() && $desired !== UserRole::Admin && $admins->count() <= 1, 422,
                'At least one administrator must remain.');

            if ($target->role !== $desired) {
                $target->update(['role' => $desired->value]);
            }
        });

        return back()->with('success', 'Account role updated.');
    }
}
