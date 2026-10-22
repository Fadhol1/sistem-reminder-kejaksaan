<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('perkara_id')->nullable()->constrained('perkaras')->cascadeOnDelete();
            $table->foreignId('perkara_reminder_id')->nullable()->constrained('perkara_reminders')->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->string('idempotency_key', 120)->unique();
            $table->string('title', 255);
            $table->text('body');
            $table->json('data_payload')->nullable();
            $table->string('fcm_message_id', 255)->nullable();
            $table->string('status', 30)->default('PENDING')->index(); // PENDING, SENT, FAILED, INVALID_TOKEN, NO_TOKEN
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
