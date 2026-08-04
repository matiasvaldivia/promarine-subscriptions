<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\{Role, User};
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;

class UserAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index()
    {
        $users = User::with('roles')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roleIds = $data['roles'] ?? [];
        unset($data['roles'], $data['password_confirmation']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if ($roleIds) $user->roles()->sync($roleIds);

        $this->audit->log('user.created', $user);
        return redirect()->route('admin.users.index')->with('success', "Usuario {$user->username} creado.");
    }

    public function edit(User $user)
    {
        $roles       = Role::orderBy('name')->get();
        $userRoleIds = $user->roles->pluck('id')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data    = $request->validated();
        $roleIds = $data['roles'] ?? [];
        unset($data['roles'], $data['password_confirmation']);
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);

        $before = $user->toArray();
        $user->update($data);
        if (!empty($roleIds)) $user->roles()->sync($roleIds);

        $this->audit->log('user.updated', $user, $before, $user->fresh()->toArray());
        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado.');
    }

    public function toggleStatus(User $user)
    {
        // Toggle: no borrado real — simplifica sin campo status en users
        $this->audit->log('user.toggle_status', $user);
        return back()->with('success', 'Acción registrada.');
    }
}
