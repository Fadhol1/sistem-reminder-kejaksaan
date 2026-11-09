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
            $table->string('tahap_sebelumnya')->nullable()->after('tahap');
            $table->string('status_sebelumnya')->nullable()->after('tahap_sebelumnya');
            $table->boolean('is_reverted')->default(false)->after('catatan');
            $table->timestamp('reverted_at')->nullable()->after('is_reverted');
            $table->foreignId('reverted_by')->nullable()->after('reverted_at')->constrained('users')->nullOnDelete();
            $table->text('revert_reason')->nullable()->after('reverted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perkara_timelines', function (Blueprint $table) {
            $table->dropForeign(['reverted_by']);
            $table->dropColumn([
                'tahap_sebelumnya',
                'status_sebelumnya',
                'is_reverted',
                'reverted_at',
                'reverted_by',
                'revert_reason',
            ]);
        });
    }
};

