<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penyidiks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perkara_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
        });

        // Migrasikan data penyidik yang sudah ada di tabel perkaras ke tabel penyidiks
        $perkaras = DB::table('perkaras')->whereNotNull('penyidik')->where('penyidik', '!=', '')->get();
        foreach ($perkaras as $perkara) {
            DB::table('penyidiks')->insert([
                'perkara_id' => $perkara->id,
                'nama' => $perkara->penyidik,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penyidiks');
    }
};
