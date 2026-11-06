<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('nama_lengkap', 100);
            $table->enum('role', ['manajemen', 'teknisi1', 'teknisi2']);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mesin', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_mesin', 30)->unique();
            $table->string('nama_mesin', 100);
            $table->string('merk_tipe', 100)->nullable();
            $table->unsignedInteger('jumlah_posisi')->default(216);
            $table->year('tahun_instalasi')->nullable();
            $table->enum('status_kondisi', ['running', 'breakdown', 'maintenance', 'idle'])->default('running');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_maintenance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mesin_id')->constrained('mesin')->cascadeOnDelete();
            $table->date('tanggal_rencana');
            $table->date('tanggal_realisasi')->nullable();
            $table->enum('tipe_maintenance', ['rutin_2bulan', 'overhaul', 'inspeksi_khusus'])->default('rutin_2bulan');
            $table->enum('status', ['terjadwal', 'proses', 'selesai', 'terlambat'])->default('terjadwal');
            $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_perawatan')->nullable();
            $table->text('catatan_realisasi')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['status', 'tanggal_rencana']);
        });

        Schema::create('operasional_mesin', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mesin_id')->constrained('mesin')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('shift', ['Shift 1', 'Shift 2', 'Shift 3']);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('eff_persen', 5, 2);
            $table->string('no_dog_break', 50)->nullable();
            $table->unsignedInteger('total_break')->default(0);
            $table->enum('status_idle', ['None', 'Permanen', 'Non-Permanen'])->default('None');
            $table->string('vessel_status', 100)->nullable();
            $table->text('anomali')->nullable();
            $table->time('jam_start');
            $table->time('jam_stop');
            $table->string('material', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tanggal', 'shift']);
        });

        Schema::create('reject_packing_header', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mesin_id')->constrained('mesin')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('nama_operator', 100);
            $table->string('no_dog', 50);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reject_packing_detail', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('header_id')->constrained('reject_packing_header')->cascadeOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->string('nomor_posisi', 30);
            $table->enum('item_reject', ['Broken filament', 'X stitch', 'Double tail', 'Tanpa tail', 'Bulging', 'DP', 'Static', 'Saddle', 'Gulungan kena oil', 'Layer falling', 'Over diameter']);
            $table->string('keterangan', 255)->nullable();
        });

        Schema::create('log_maintenance_harian', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('mesin_id')->constrained('mesin')->cascadeOnDelete();
            $table->string('posisi_mesin', 50);
            $table->enum('status_idle', ['Permanen', 'Non-Permanen', 'None'])->default('Non-Permanen');
            $table->text('problem');
            $table->text('action');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status_perbaikan', ['proses', 'selesai', 'pending_sparepart', 'monitoring'])->default('proses');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tanggal', 'mesin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_maintenance_harian');
        Schema::dropIfExists('reject_packing_detail');
        Schema::dropIfExists('reject_packing_header');
        Schema::dropIfExists('operasional_mesin');
        Schema::dropIfExists('jadwal_maintenance');
        Schema::dropIfExists('mesin');
        Schema::dropIfExists('users');
    }
};