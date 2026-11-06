<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userId = DB::selectOne("SHOW COLUMNS FROM `users` LIKE 'id'");
        $userType = strtolower($userId->Type);
        $userForeignType = str_starts_with($userType, 'bigint') ? 'BIGINT' : 'INT';
        if (str_contains($userType, 'unsigned')) {
            $userForeignType .= ' UNSIGNED';
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'asal_tim')) {
                $table->enum('asal_tim', ['dty', 'indorama'])->default('dty');
            }
            if (!Schema::hasColumn('users', 'bidang_teknis')) {
                $table->enum('bidang_teknis', ['mekanik', 'elektrik', 'instrumen'])->nullable();
            }
        });

        DB::table('users')->where('role', 'teknisi')->whereNull('bidang_teknis')->update([
            'asal_tim' => 'dty',
            'bidang_teknis' => 'mekanik',
        ]);

        Schema::table('log_maintenance_harian', function (Blueprint $table): void {
            if (!Schema::hasColumn('log_maintenance_harian', 'tindak_lanjut')) {
                $table->text('tindak_lanjut')->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'ditangani_oleh')) {
                $table->integer('ditangani_oleh')->nullable();
            }
        });

        $handlerColumn = DB::selectOne("SHOW COLUMNS FROM `log_maintenance_harian` LIKE 'ditangani_oleh'");
        $handlerType = strtolower($handlerColumn->Type);
        if (str_starts_with($handlerType, 'bigint') !== str_starts_with($userType, 'bigint')
            || str_contains($handlerType, 'unsigned') !== str_contains($userType, 'unsigned')) {
            DB::statement("ALTER TABLE `log_maintenance_harian` MODIFY `ditangani_oleh` {$userForeignType} NULL");
        }

        Schema::table('log_maintenance_harian', function (Blueprint $table): void {
            $table->foreign('ditangani_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('log_maintenance_harian', function (Blueprint $table): void {
            $table->dropForeign(['ditangani_oleh']);
            $table->dropColumn(['ditangani_oleh', 'tindak_lanjut']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['asal_tim', 'bidang_teknis']);
        });
    }
};