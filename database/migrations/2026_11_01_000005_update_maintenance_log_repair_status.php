<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE log_maintenance_harian MODIFY status_perbaikan ENUM('proses', 'selesai', 'pending_sparepart', 'monitoring') NOT NULL DEFAULT 'proses'");
    }

    public function down(): void
    {
        DB::table('log_maintenance_harian')
            ->where('status_perbaikan', 'proses')
            ->update(['status_perbaikan' => 'monitoring']);

        DB::statement("ALTER TABLE log_maintenance_harian MODIFY status_perbaikan ENUM('selesai', 'pending_sparepart', 'monitoring') NOT NULL DEFAULT 'selesai'");
    }
};
