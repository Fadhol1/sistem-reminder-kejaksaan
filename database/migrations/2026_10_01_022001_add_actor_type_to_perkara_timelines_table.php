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
        Schema::table('perkara_timelines', function (Blueprint $table) {
            $table->string('actor_type')->default('USER')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perkara_timelines', function (Blueprint $table) {
            $table->dropColumn('actor_type');
        });
    }
};
