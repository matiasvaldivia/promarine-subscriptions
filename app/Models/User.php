<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'last_login_at'          => 'datetime',
            'locked_until'           => 'datetime',
        ];
    }

    // ── Roles & Permissions ─────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->some(fn ($role) => $role->hasPermission($permission));
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    // ── Relations ───────────────────────────────────────────────────

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function administrativeNotes(): HasMany
    {
        return $this->hasMany(AdministrativeNote::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(ShopifySyncRun::class, 'created_by');
    }
}
