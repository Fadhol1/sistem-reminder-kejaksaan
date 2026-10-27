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
        // 1. Migrasikan user yang sebelumnya memiliki role 'penyidik' menjadi 'jaksa'
        DB::table('users')->where('role', 'penyidik')->update(['role' => 'jaksa']);

        // 2. Modifikasi definisi kolom role pada MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pidum', 'jaksa') NOT NULL DEFAULT 'jaksa'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pidum', 'jaksa', 'penyidik') NOT NULL DEFAULT 'jaksa'");
        }
    }
};
