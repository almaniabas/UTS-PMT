<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('users')->insert([
            ['username' => 'manajemen', 'password' => Hash::make('admin123'), 'nama_lengkap' => 'Supervisor Indorama', 'role' => 'manajemen', 'status' => 'aktif', 'created_at' => $now],
            ['username' => 'teknisi1', 'password' => Hash::make('admin123'), 'nama_lengkap' => 'Ahmad Teknisi', 'role' => 'teknisi1', 'status' => 'aktif', 'asal_tim' => 'dty', 'bidang_teknis' => 'mekanik', 'created_at' => $now],
            ['username' => 'teknisi2', 'password' => Hash::make('admin123'), 'nama_lengkap' => 'Teknisi Indorama', 'role' => 'teknisi2', 'status' => 'aktif', 'asal_tim' => 'indorama', 'bidang_teknis' => 'elektrik', 'created_at' => $now],
        ]);

        DB::table('mesin')->insert([
            ['kode_mesin' => 'MC-01', 'nama_mesin' => 'DTY Machine Line 1', 'merk_tipe' => 'Barmag FK6-1000', 'jumlah_posisi' => 216, 'tahun_instalasi' => 2018, 'status_kondisi' => 'running', 'catatan' => 'Kondisi stabil, running POY 150D', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mesin' => 'MC-02', 'nama_mesin' => 'DTY Machine Line 2', 'merk_tipe' => 'Barmag FK6-1000', 'jumlah_posisi' => 216, 'tahun_instalasi' => 2019, 'status_kondisi' => 'running', 'catatan' => 'Normal operation, shift 24 jam', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mesin' => 'MC-03', 'nama_mesin' => 'DTY Machine Line 3', 'merk_tipe' => 'Murata 33H', 'jumlah_posisi' => 192, 'tahun_instalasi' => 2020, 'status_kondisi' => 'breakdown', 'catatan' => 'Vessel heater 2 menunggu suku cadang', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mesin' => 'MC-04', 'nama_mesin' => 'DTY Machine Line 4', 'merk_tipe' => 'Murata 33H', 'jumlah_posisi' => 192, 'tahun_instalasi' => 2021, 'status_kondisi' => 'maintenance', 'catatan' => 'Sedang menjalani preventive maintenance', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('jadwal_maintenance')->insert([
            ['mesin_id' => 4, 'tanggal_rencana' => today(), 'tipe_maintenance' => 'rutin_2bulan', 'status' => 'proses', 'teknisi_id' => 2, 'catatan_perawatan' => 'Pembersihan heater plate, ganti oil traverse box', 'created_at' => $now],
            ['mesin_id' => 1, 'tanggal_rencana' => today()->addDays(14), 'tipe_maintenance' => 'rutin_2bulan', 'status' => 'terjadwal', 'teknisi_id' => 2, 'catatan_perawatan' => 'Cek bearing spindle dan kalibrasi sensor tension', 'created_at' => $now],
            ['mesin_id' => 2, 'tanggal_rencana' => today()->addDays(28), 'tipe_maintenance' => 'rutin_2bulan', 'status' => 'terjadwal', 'teknisi_id' => null, 'catatan_perawatan' => 'Inspeksi belt drive dan inverter motor', 'created_at' => $now],
        ]);
    }
}