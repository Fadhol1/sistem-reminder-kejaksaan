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
        Schema::table('perkaras', function (Blueprint $table) {
            $table->string('bidang')->default('PIDUM')->after('jaksa');
            $table->string('satuan_kerja')->nullable()->after('bidang');
        });

        Schema::table('perkara_timelines', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('catatan');
        });

        Schema::table('perkara_reminders', function (Blueprint $table) {
            $table->string('tahap')->nullable()->after('perkara_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perkara_reminders', function (Blueprint $table) {
            $table->dropColumn('tahap');
        });

        Schema::table('perkara_timelines', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('perkaras', function (Blueprint $table) {
            $table->dropColumn(['bidang', 'satuan_kerja']);
        });
    }
};
