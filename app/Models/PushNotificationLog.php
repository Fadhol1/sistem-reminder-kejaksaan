<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $table = 'push_notification_logs';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_INVALID_TOKEN = 'INVALID_TOKEN';
    public const STATUS_NO_TOKEN = 'NO_TOKEN';

    protected $fillable = [
        'user_id',
        'perkara_id',
        'perkara_reminder_id',
        'event_type',
        'idempotency_key',
        'title',
        'body',
        'data_payload',
        'fcm_message_id',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function perkara(): BelongsTo
    {
        return $this->belongsTo(Perkara::class, 'perkara_id');
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(PerkaraReminder::class, 'perkara_reminder_id');
    }

    /**
     * Check if a notification has already been successfully sent or recorded for an idempotency key.
     */
    public static function hasIdempotencyKey(string $key): bool
    {
        return self::where('idempotency_key', $key)->exists();
    }
}
