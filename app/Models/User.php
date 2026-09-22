<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
     * Relasi ke perkara di mana user ditugaskan sebagai Jaksa (berdasarkan nama).
     */
    public function perkaraJaksa(): HasMany
    {
        return $this->hasMany(Perkara::class, 'jaksa', 'name');
    }

    /**
     * Relasi ke perkara di mana user ditugaskan sebagai Penyidik (berdasarkan nama).
     */
    public function perkaraPenyidik(): HasMany
    {
        return $this->hasMany(Perkara::class, 'penyidik', 'name');
    }

    /**
     * Role checking helpers
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isJaksa(): bool
    {
        return $this->role === 'jaksa';
    }

    public function isPenyidik(): bool
    {
        return $this->role === 'penyidik';
    }
}
