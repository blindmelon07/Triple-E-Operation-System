<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Auditable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Auditable;

    protected static function getAuditExcludedFields(): array
    {
        return ['password', 'remember_token', 'email_verified_at'];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The HR/attendance master record linked to this login, if any. Not
     * every User has one, and not every Employee has a User (see
     * App\Models\Employee).
     *
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Any authenticated user may enter the panel — access to individual
     * resources/pages within it is still gated per-permission by Filament
     * Shield's policies. Without this, Filament's Authenticate middleware
     * falls back to `config('app.env') === 'local'`, which would lock every
     * user out of the panel entirely as soon as APP_ENV is anything else
     * (production, testing, staging) — invisible on a local dev box, fatal
     * everywhere else.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
