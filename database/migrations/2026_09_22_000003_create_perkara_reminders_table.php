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
        Schema::create('perkara_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perkara_id')
                  ->constrained('perkaras')
                  ->onDelete('cascade');
            $table->string('jenis_reminder');
            $table->date('tanggal_mulai');
            $table->date('deadline');
            $table->enum('status_urgensi', ['Aman', 'H-3', 'Jatuh Tempo', 'Overdue'])->default('Aman');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perkara_reminders');
    }
};
