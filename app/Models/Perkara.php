<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Tersangka;
use App\Models\Penyidik;

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
        'penyidik',
        'jaksa',
        'bidang',
        'satuan_kerja',
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

    public function tersangkas(): HasMany
    {
        return $this->hasMany(Tersangka::class, 'perkara_id');
    }

    public function penyidiks(): HasMany
    {
        return $this->hasMany(Penyidik::class, 'perkara_id');
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

    /**
     * Relasi Many-to-Many ke User Jaksa via pivot perkara_jaksa.
     */
    public function jaksas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'perkara_jaksa', 'perkara_id', 'jaksa_id');
    }

    /**
     * Mengecek apakah perkara pada tahap Koordinasi Awal telah overdue / terlewat batas SLA 3 hari.
     */
    public function isKoordinasiAwalOverdue(): bool
    {
        if ($this->tahap_saat_ini !== \App\Services\PerkaraWorkflowService::TAHAP_KOORDINASI) {
            return false;
        }

        // 1. Status kegiatan sudah berstatus Overdue / SOP Form-1B & Form-1C
        if (str_contains($this->status_saat_ini, 'Overdue')) {
            return true;
        }

        // 2. Reminder aktif Koordinasi Awal berstatus Overdue
        $hasActiveOverdue = $this->relationLoaded('activeReminders')
            ? $this->activeReminders->contains('status_urgensi', 'Overdue')
            : $this->activeReminders()->where('status_urgensi', 'Overdue')->exists();

        if ($hasActiveOverdue) {
            return true;
        }

        // 3. Reminder Koordinasi Awal sudah melewati deadline atau berstatus Overdue
        $reminder = $this->relationLoaded('reminders')
            ? $this->reminders->where('tahap', \App\Services\PerkaraWorkflowService::TAHAP_KOORDINASI)->sortByDesc('id')->first()
            : $this->reminders()->where('tahap', \App\Services\PerkaraWorkflowService::TAHAP_KOORDINASI)->latest('id')->first();

        if ($reminder) {
            if ($reminder->status_urgensi === 'Overdue') {
                return true;
            }
            if ($reminder->deadline && \Carbon\Carbon::parse($reminder->deadline)->startOfDay()->isPast()) {
                return true;
            }
        }

        return false;
    }
}
