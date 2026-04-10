<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends BaseApiController
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $role = trim((string)$request->query('role', ''));
        $status = trim((string)$request->query('status', ''));
        $deleted = trim((string)$request->query('deleted', 'exclude')); // exclude|include|only

        $perPage = (int)$request->query('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) $perPage = 15;

        $sortBy = trim((string)$request->query('sort_by', 'id'));
        $sortDir = strtolower(trim((string)$request->query('sort_dir', 'asc'))) === 'desc' ? 'desc' : 'asc';

        $allowedSort = ['id', 'name', 'email', 'role', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'id';

        $query = User::query()
            ->select('id', 'name', 'email', 'role', 'status', 'bio', 'deleted_at', 'created_at', 'updated_at');

        if ($deleted === 'include') {
            $query->withTrashed();
        } elseif ($deleted === 'only') {
            $query->onlyTrashed();
        }

        if ($q !== '') {
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($role !== '') $query->where('role', $role);
        if ($status !== '') $query->where('status', $status);

        $users = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->appends($request->query());

        return $this->success($users);
    }

    public function show(User $user)
    {
        return $this->success($user->only([
            'id', 'name', 'email', 'role', 'status', 'bio', 'created_at', 'updated_at'
        ]));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'user',
            'status' => $data['status'] ?? 'active',
            'bio' => $data['bio'] ?? null,
            'avatar' => null,
        ]);

        return $this->success(
            $user->fresh()->only(['id','name','email','role','status','bio','created_at']),
            'User created.'
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $currentUser = $request->user();

        if (isset($data['role'])) {
            $this->assertCanChangeRole($currentUser, $user, $data['role']);
            $user->role = $data['role'];
        }

        if (isset($data['status'])) {
            $this->assertCanChangeStatus($currentUser, $user, $data['status']);
            $user->status = $data['status'];
        }

        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (array_key_exists('bio', $data)) $user->bio = $data['bio'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $this->success(
            $user->fresh()->only(['id','name','email','role','status','bio','created_at']),
            'User updated.'
        );
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        $data = $request->validated();
        $currentUser = $request->user();

        $this->assertCanChangeRole($currentUser, $user, $data['role']);

        $user->role = $data['role'];
        $user->save();

        return $this->success($user->only(['id','name','email','role','status','created_at']), 'User role updated.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $data = $request->validated();
        $currentUser = $request->user();

        $this->assertCanChangeStatus($currentUser, $user, $data['status']);

        $user->status = $data['status'];
        $user->save();

        return $this->success($user->only(['id','name','email','role','status','created_at']), 'User status updated.');
    }

    // DELETE /api/admin/users/{user} (soft delete)
    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        // 1) Không cho tự xóa chính mình
        if ($currentUser && $currentUser->id === $user->id) {
            return $this->error('You cannot delete yourself.', 422);
        }

        // 2) Không cho xóa admin cuối cùng
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return $this->error('At least one admin must remain in the system.', 422);
            }
        }

        $user->delete();

        return $this->success(null, 'User deleted.');
    }

    // PATCH /api/admin/users/{id}/restore
    public function restore(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return $this->error('User is not deleted.', 422);
        }

        $user->restore();

        return $this->success(
            $user->fresh()->only(['id','name','email','role','status','bio','created_at','updated_at']),
            'User restored.'
        );
    }

    private function assertCanChangeRole(?User $currentUser, User $targetUser, string $newRole): void
    {
        if ($currentUser && $currentUser->id === $targetUser->id && $newRole !== 'admin') {
            abort(response()->json([
                'success' => false,
                'data' => null,
                'message' => 'You cannot change your own role.',
            ], 422));
        }

        if ($targetUser->role === 'admin' && $newRole !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                abort(response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'At least one admin must remain in the system.',
                ], 422));
            }
        }
    }

    private function assertCanChangeStatus(?User $currentUser, User $targetUser, string $newStatus): void
    {
        if ($currentUser && $currentUser->id === $targetUser->id && $newStatus === 'blocked') {
            abort(response()->json([
                'success' => false,
                'data' => null,
                'message' => 'You cannot block yourself.',
            ], 422));
        }

        if ($targetUser->role === 'admin' && $targetUser->status === 'active' && $newStatus === 'blocked') {
            $activeAdminCount = User::where('role', 'admin')->where('status', 'active')->count();
            if ($activeAdminCount <= 1) {
                abort(response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'At least one active admin must remain in the system.',
                ], 422));
            }
        }
    }
}
