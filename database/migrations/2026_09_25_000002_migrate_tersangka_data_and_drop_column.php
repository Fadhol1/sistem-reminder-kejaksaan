<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $perkaras = DB::table('perkaras')->whereNotNull('nama_tersangka')->get();

        foreach ($perkaras as $perkara) {
            $namaList = array_map('trim', explode(',', $perkara->nama_tersangka));

            foreach ($namaList as $nama) {
                if ($nama !== '') {
                    DB::table('tersangkas')->insert([
                        'perkara_id' => $perkara->id,
                        'nama' => $nama,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::table('perkaras', function (Blueprint $table) {

            $table->dropColumn('nama_tersangka');
        });
    }

    public function down(): void
    {
        Schema::table('perkaras', function (Blueprint $table) {
            $table->string('nama_tersangka')->nullable()->after('nomor_perkara');
        });
        $perkaras = DB::table('tersangkas')->select('perkara_id', DB::raw('GROUP_CONCAT(nama) as nama'))->groupBy('perkara_id')->get();

        foreach ($perkaras as $perkara) {
            DB::table('perkaras')->where('id', $perkara->perkara_id)->update(['nama_tersangka' => $perkara->nama]);
        }

        Schema::dropIfExists('tersangkas');
    }
};
