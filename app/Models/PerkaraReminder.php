<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerkaraReminder extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'perkara_reminders';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'perkara_id',
        'tahap',
        'jenis_reminder',
        'tanggal_mulai',
        'deadline',
        'status_urgensi',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi kembali ke data Perkara utama.
     */
    public function perkara(): BelongsTo
    {
        return $this->belongsTo(Perkara::class, 'perkara_id');
    }

    /**
     * Scope untuk mengambil reminder yang masih aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk mengambil reminder dengan urgensi tinggi (H-3, Jatuh Tempo, Overdue).
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->whereIn('status_urgensi', ['H-3', 'Jatuh Tempo', 'Overdue']);
    }

    /**
     * Hitung status urgensi otomatis berdasarkan deadline.
     */
    public static function determineUrgensi($deadline): string
    {
        $deadlineDate = \Carbon\Carbon::parse($deadline)->startOfDay();
        $today = now()->startOfDay();
        $diff = (int) $today->diffInDays($deadlineDate, false);

        if ($diff < 0) {
            return 'Overdue';
        }
        if ($diff === 0) {
            return 'Jatuh Tempo';
        }
        if ($diff <= 3) {
            return 'H-3';
        }

        return 'Aman';
    }

    /**
     * Accessor sisa hari sebelum deadline (negatif jika sudah lewat).
     */
    public function getSisaHariAttribute(): ?int
    {
        if (! $this->deadline) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->deadline)->startOfDay(), false);
    }

    /**
     * Sinkronisasi status urgensi dengan tanggal hari ini.
     */
    public function syncStatusUrgensi(): bool
    {
        $calculated = self::determineUrgensi($this->deadline);
        if ($this->status_urgensi !== $calculated) {
            $this->status_urgensi = $calculated;
            return $this->save();
        }

        return false;
    }
}
