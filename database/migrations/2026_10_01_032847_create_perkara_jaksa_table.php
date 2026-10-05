<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perkara_jaksa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perkara_id')->constrained('perkaras')->cascadeOnDelete();
            $table->foreignId('jaksa_id')->constrained('users')->cascadeOnDelete();
            
            // Mencegah duplikasi entri relasi yang sama untuk 1 jaksa di perkara yang sama
            $table->unique(['perkara_id', 'jaksa_id']);
        });

        // ==========================================
        // DATA MIGRATION: Pindahkan relasi string lama
        // ==========================================
        $perkaras = DB::table('perkaras')->whereNotNull('jaksa')->get();
        foreach ($perkaras as $perkara) {
            $jaksaUser = DB::table('users')->where('name', $perkara->jaksa)->first();
            if ($jaksaUser) {
                // insert to pivot
                DB::table('perkara_jaksa')->insertOrIgnore([
                    'perkara_id' => $perkara->id,
                    'jaksa_id'   => $jaksaUser->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perkara_jaksa');
    }
};
