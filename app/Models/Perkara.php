<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Perkara extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'perkaras';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nomor_perkara',
        'nama_tersangka',
        'penyidik',
        'jaksa',
        'tanggal_spdp',
        'tahap_saat_ini',
        'status_saat_ini',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_spdp' => 'date',
        ];
    }

    /**
     * Relasi ke seluruh riwayat linimasa (perkara_timelines).
     */
    public function timelines(): HasMany
    {
        return $this->hasMany(PerkaraTimeline::class, 'perkara_id');
    }

    /**
     * Relasi ke seluruh reminder perkara (perkara_reminders).
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(PerkaraReminder::class, 'perkara_id');
    }

    /**
     * Relasi ke reminder yang masih aktif.
     */
    public function activeReminders(): HasMany
    {
        return $this->hasMany(PerkaraReminder::class, 'perkara_id')->where('is_active', true);
    }

    /**
     * Mengambil riwayat linimasa perkembangan perkara paling terakhir.
     */
    public function latestTimeline(): HasOne
    {
        return $this->hasOne(PerkaraTimeline::class, 'perkara_id')->latestOfMany('tanggal_kejadian');
    }

    /**
     * Relasi opsional ke User jaksa (pencocokan nama ke users.name).
     */
    public function jaksaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jaksa', 'name');
    }

    /**
     * Relasi opsional ke User penyidik (pencocokan nama ke users.name).
     */
    public function penyidikUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penyidik', 'name');
    }
}
