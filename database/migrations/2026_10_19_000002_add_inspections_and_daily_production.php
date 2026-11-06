<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function parentType(string $table): array
    {
        $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
        $type = strtolower($column->Type);

        return [str_starts_with($type, 'bigint'), str_contains($type, 'unsigned')];
    }

    private function addForeignId(Blueprint $table, string $name, array $type): void
    {
        [$bigInteger, $unsigned] = $type;

        if ($bigInteger) {
            $unsigned ? $table->unsignedBigInteger($name) : $table->bigInteger($name);
        } else {
            $unsigned ? $table->unsignedInteger($name) : $table->integer($name);
        }
    }

    public function up(): void
    {
        $machineIdType = $this->parentType('mesin');
        $userIdType = $this->parentType('users');

        Schema::table('log_maintenance_harian', function (Blueprint $table): void {
            if (!Schema::hasColumn('log_maintenance_harian', 'area_kerusakan')) {
                $table->string('area_kerusakan', 30)->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'jumlah_posisi_terdampak')) {
                $table->unsignedSmallInteger('jumlah_posisi_terdampak')->default(1);
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'waktu_mulai')) {
                $table->time('waktu_mulai')->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'waktu_selesai')) {
                $table->time('waktu_selesai')->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'downtime_menit')) {
                $table->unsignedInteger('downtime_menit')->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'eskalasi_ke')) {
                $table->string('eskalasi_ke', 100)->nullable();
            }
            if (!Schema::hasColumn('log_maintenance_harian', 'status_eskalasi')) {
                $table->enum('status_eskalasi', ['tidak_perlu', 'perlu_dikirim', 'sudah_dikirim', 'ditangani'])->default('tidak_perlu');
            }
        });

        if (!Schema::hasTable('inspeksi_harian')) {
            Schema::create('inspeksi_harian', function (Blueprint $table) use ($machineIdType, $userIdType): void {
                $table->id();
                $this->addForeignId($table, 'mesin_id', $machineIdType);
                $this->addForeignId($table, 'pc_id', $userIdType);
                $table->date('tanggal');
                $table->unsignedSmallInteger('total_posisi');
                $table->unsignedSmallInteger('posisi_bermasalah')->default(0);
                $table->enum('keputusan_mesin', ['running', 'stop'])->default('running');
                $table->text('catatan')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['mesin_id', 'tanggal']);
                $table->foreign('mesin_id')->references('id')->on('mesin')->cascadeOnDelete();
                $table->foreign('pc_id')->references('id')->on('users')->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('inspeksi_posisi')) {
            Schema::create('inspeksi_posisi', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inspeksi_id')->constrained('inspeksi_harian')->cascadeOnDelete();
                $table->unsignedSmallInteger('nomor_posisi');
                $table->enum('status', ['normal', 'problem'])->default('normal');
                $table->string('jenis_problem', 60)->nullable();
                $table->string('catatan', 255)->nullable();
                $table->unique(['inspeksi_id', 'nomor_posisi']);
                $table->index(['status', 'jenis_problem']);
            });
        }

        if (!Schema::hasTable('produksi_harian')) {
            Schema::create('produksi_harian', function (Blueprint $table) use ($machineIdType, $userIdType): void {
                $table->id();
                $this->addForeignId($table, 'mesin_id', $machineIdType);
                $this->addForeignId($table, 'input_oleh', $userIdType);
                $table->date('tanggal');
                $table->enum('grade', ['AA', 'A', 'B', 'C', 'WASTE']);
                $table->decimal('target_kg', 12, 2)->default(0);
                $table->decimal('aktual_kg', 12, 2)->default(0);
                $table->text('catatan')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['mesin_id', 'tanggal', 'grade']);
                $table->foreign('mesin_id')->references('id')->on('mesin')->restrictOnDelete();
                $table->foreign('input_oleh')->references('id')->on('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_harian');
        Schema::dropIfExists('inspeksi_posisi');
        Schema::dropIfExists('inspeksi_harian');

        Schema::table('log_maintenance_harian', function (Blueprint $table): void {
            $columns = ['area_kerusakan', 'jumlah_posisi_terdampak', 'waktu_mulai', 'waktu_selesai', 'downtime_menit', 'eskalasi_ke', 'status_eskalasi'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('log_maintenance_harian', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};