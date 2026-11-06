<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $parentColumn = DB::selectOne("SHOW COLUMNS FROM `log_maintenance_harian` LIKE 'id'");
        $parentType = strtolower($parentColumn->Type);
        $isBigInteger = str_starts_with($parentType, 'bigint');
        $isUnsigned = str_contains($parentType, 'unsigned');
        $foreignColumnType = ($isBigInteger ? 'BIGINT' : 'INT') . ($isUnsigned ? ' UNSIGNED' : '');

        if (!Schema::hasTable('maintenance_log_spare_parts')) {
            Schema::create('maintenance_log_spare_parts', function (Blueprint $table) use ($isBigInteger, $isUnsigned): void {
                $table->id();
                if ($isBigInteger) {
                    $isUnsigned ? $table->unsignedBigInteger('maintenance_log_id') : $table->bigInteger('maintenance_log_id');
                } else {
                    $isUnsigned ? $table->unsignedInteger('maintenance_log_id') : $table->integer('maintenance_log_id');
                }
                $table->string('nama_part', 120);
                $table->string('nomor_part', 60)->nullable();
                $table->decimal('jumlah', 10, 2)->default(1);
                $table->string('satuan', 20)->default('pcs');
                $table->decimal('harga_satuan', 12, 2)->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['nama_part', 'created_at']);
            });
        }

        $foreignColumn = DB::selectOne("SHOW COLUMNS FROM `maintenance_log_spare_parts` LIKE 'maintenance_log_id'");
        $foreignType = strtolower($foreignColumn->Type);
        $hasMatchingType = str_starts_with($foreignType, $isBigInteger ? 'bigint' : 'int')
            && str_contains($foreignType, 'unsigned') === $isUnsigned;

        if (!$hasMatchingType) {
            DB::statement("ALTER TABLE `maintenance_log_spare_parts` MODIFY `maintenance_log_id` {$foreignColumnType} NOT NULL");
        }

        Schema::table('maintenance_log_spare_parts', function (Blueprint $table): void {
            $table->foreign('maintenance_log_id')->references('id')->on('log_maintenance_harian')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_log_spare_parts');
    }
};