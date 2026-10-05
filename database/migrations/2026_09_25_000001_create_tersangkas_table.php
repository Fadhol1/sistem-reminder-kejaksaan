<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::create('tersangkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perkara_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tersangkas');
    }
};
