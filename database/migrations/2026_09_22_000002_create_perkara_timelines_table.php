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
        Schema::create('perkara_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perkara_id')
                  ->constrained('perkaras')
                  ->onDelete('cascade');
            $table->string('tahap');
            $table->string('status_kegiatan');
            $table->date('tanggal_kejadian');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perkara_timelines');
    }
};
