<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerkaraTimeline extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'perkara_timelines';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'perkara_id',
        'user_id',
        'actor_type',
        'tahap',
        'tahap_sebelumnya',
        'status_sebelumnya',
        'status_kegiatan',
        'tanggal_kejadian',
        'catatan',
        'is_reverted',
        'reverted_at',
        'reverted_by',
        'revert_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kejadian' => 'date:Y-m-d',
            'is_reverted' => 'boolean',
            'reverted_at' => 'datetime',
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
     * Relasi ke User yang mencatat kejadian.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke User yang membatalkan kejadian (Admin PIDUM).
     */
    public function reverter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }
}
