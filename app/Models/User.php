<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nip',
        'nrp',
        'nomor_hp',
        'pangkat_golongan',
        'password',
        'role',
        'jabatan',
        'foto_profil',
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
     * Relasi Many-to-Many perkara jika user adalah Jaksa (berdasarkan id via pivot perkaras_jaksa).
     */
    public function perkaras(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Perkara::class, 'perkara_jaksa', 'jaksa_id', 'perkara_id');
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
    public function isPidum(): bool
    {
        return $this->role === 'pidum';
    }

    public function isJaksa(): bool
    {
        return $this->role === 'jaksa';
    }

    /**
     * @deprecated Role penyidik telah dihapus dari sistem (penyidik adalah entitas eksternal)
     */
    public function isPenyidik(): bool
    {
        return false;
    }

    /**
     * Relasi ke seluruh token perangkat FCM yang terdaftar untuk user ini.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class, 'user_id');
    }

    /**
     * Relasi ke token perangkat FCM yang masih aktif.
     */
    public function activeDeviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class, 'user_id')->where('is_active', true);
    }

    /**
     * Relasi ke seluruh riwayat/log push notification untuk user ini.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(PushNotificationLog::class, 'user_id');
    }

    protected $appends = [
        'foto_profil_url',
        'foto_profile_url',
    ];

    /**
     * Menghasilkan URL lengkap foto profil (HTTP/HTTPS)
     */
    public function getFotoProfilUrlAttribute(): ?string
    {
        if (!$this->foto_profil) {
            return null;
        }

        return Storage::disk('public')->url($this->foto_profil);
    }

    /**
     * Alias bahasa Inggris untuk kompatibilitas aplikasi mobile
     */
    public function getFotoProfileUrlAttribute(): ?string
    {
        return $this->foto_profil_url;
    }
}
