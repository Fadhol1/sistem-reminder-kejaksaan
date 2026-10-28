<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Sesuaikan data timestamp sebelumnya yang tercatat dalam UTC (+00:00) ke WIB (+07:00)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE perkara_timelines SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE perkaras SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE perkara_reminders SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE tersangkas SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE penyidiks SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE perkara_timelines SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours')");
            DB::statement("UPDATE perkaras SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours')");
            DB::statement("UPDATE perkara_reminders SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours')");
            DB::statement("UPDATE tersangkas SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours')");
            DB::statement("UPDATE penyidiks SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours')");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE perkara_timelines SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE perkaras SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE perkara_reminders SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE tersangkas SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)");
            DB::statement("UPDATE penyidiks SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE perkara_timelines SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours')");
            DB::statement("UPDATE perkaras SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours')");
            DB::statement("UPDATE perkara_reminders SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours')");
            DB::statement("UPDATE tersangkas SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours')");
            DB::statement("UPDATE penyidiks SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours')");
        }
    }
};
