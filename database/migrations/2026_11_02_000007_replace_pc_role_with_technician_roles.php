<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function detachDeletedUsers(string $table, string $column, array $userIds): void
    {
        if (!$userIds || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $foreignKeys = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME = ?',
            [$table, $column, 'users']
        );
        foreach ($foreignKeys as $foreignKey) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                $blueprint->dropForeign($foreignKey->CONSTRAINT_NAME);
            });
        }

        $columnType = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->Type;
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$columnType} NULL");
        DB::table($table)->whereIn($column, $userIds)->update([$column => null]);

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->foreign($column)->references('id')->on('users')->nullOnDelete();
        });
    }

    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('teknisi', 'pc', 'manajemen', 'teknisi1', 'teknisi2') NOT NULL");

        DB::table('users')
            ->where('role', 'teknisi')
            ->where('asal_tim', 'indorama')
            ->whereIn('bidang_teknis', ['elektrik', 'instrumen'])
            ->update(['role' => 'teknisi2']);

        DB::table('users')
            ->where('role', 'teknisi')
            ->update(['role' => 'teknisi1', 'asal_tim' => 'dty', 'bidang_teknis' => 'mekanik']);

        $pcUserIds = DB::table('users')->where('role', 'pc')->pluck('id')->all();
        $this->detachDeletedUsers('inspeksi_harian', 'pc_id', $pcUserIds);
        $this->detachDeletedUsers('produksi_harian', 'input_oleh', $pcUserIds);

        DB::table('users')->whereIn('id', $pcUserIds)->delete();
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('manajemen', 'teknisi1', 'teknisi2') NOT NULL");
    }

    public function down(): void
    {
        DB::table('users')->whereIn('role', ['teknisi1', 'teknisi2'])->update(['role' => 'teknisi']);
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('teknisi', 'pc', 'manajemen') NOT NULL");
    }
};
