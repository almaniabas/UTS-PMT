<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('jadwal_maintenance', 'selesai_pada')) {
            Schema::table('jadwal_maintenance', function (Blueprint $table): void {
                $table->timestamp('selesai_pada')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jadwal_maintenance', 'selesai_pada')) {
            Schema::table('jadwal_maintenance', function (Blueprint $table): void {
                $table->dropColumn('selesai_pada');
            });
        }
    }
};
