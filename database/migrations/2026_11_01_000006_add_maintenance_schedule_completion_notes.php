<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('jadwal_maintenance', 'catatan_realisasi')) {
            Schema::table('jadwal_maintenance', function (Blueprint $table): void {
                $table->text('catatan_realisasi')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jadwal_maintenance', 'catatan_realisasi')) {
            Schema::table('jadwal_maintenance', function (Blueprint $table): void {
                $table->dropColumn('catatan_realisasi');
            });
        }
    }
};
