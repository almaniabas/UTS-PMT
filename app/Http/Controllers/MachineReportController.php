<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MachineReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'mesin_id' => ['nullable', 'integer', 'exists:mesin,id'],
        ]);
        $from = $filters['from'] ?? today()->subDays(29)->toDateString();
        $to = $filters['to'] ?? today()->toDateString();

        $machineOptions = DB::table('mesin')->orderBy('kode_mesin')->get();
        $machinesQuery = DB::table('mesin')->orderBy('kode_mesin');
        if (!empty($filters['mesin_id'])) {
            $machinesQuery->where('id', $filters['mesin_id']);
        }
        $machines = $machinesQuery->get();
        $machineIds = $machines->pluck('id');

        $operations = DB::table('operasional_mesin')
            ->whereIn('mesin_id', $machineIds)->whereBetween('tanggal', [$from, $to])
            ->selectRaw('mesin_id, COUNT(*) as jumlah_shift, AVG(eff_persen) as rata_eff, SUM(total_break) as total_break')
            ->groupBy('mesin_id')->get()->keyBy('mesin_id');

        $maintenance = DB::table('log_maintenance_harian')
            ->whereIn('mesin_id', $machineIds)->whereBetween('tanggal', [$from, $to])
            ->selectRaw("mesin_id, COUNT(*) as jumlah_kerusakan, COALESCE(SUM(downtime_menit), 0) as downtime_menit, SUM(CASE WHEN status_perbaikan <> 'selesai' THEN 1 ELSE 0 END) as pekerjaan_terbuka")
            ->groupBy('mesin_id')->get()->keyBy('mesin_id');

        $inspections = DB::table('inspeksi_harian')
            ->whereIn('mesin_id', $machineIds)->whereBetween('tanggal', [$from, $to])
            ->selectRaw('mesin_id, COUNT(*) as jumlah_inspeksi, SUM(posisi_bermasalah) as posisi_bermasalah')
            ->groupBy('mesin_id')->get()->keyBy('mesin_id');

        $schedules = DB::table('jadwal_maintenance')
            ->whereIn('mesin_id', $machineIds)
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('tanggal_rencana', [$from, $to])
                    ->orWhereBetween('tanggal_realisasi', [$from, $to]);
            })
            ->selectRaw("mesin_id, SUM(CASE WHEN status = 'selesai' AND tanggal_realisasi BETWEEN ? AND ? THEN 1 ELSE 0 END) as pm_selesai, SUM(CASE WHEN status IN ('terjadwal', 'proses') AND tanggal_rencana BETWEEN ? AND ? AND tanggal_rencana < ? THEN 1 ELSE 0 END) as pm_terlambat", [$from, $to, $from, $to, today()->toDateString()])
            ->groupBy('mesin_id')->get()->keyBy('mesin_id');

        $rows = $machines->map(function ($machine) use ($operations, $maintenance, $inspections, $schedules): object {
            $operation = $operations->get($machine->id);
            $maintenanceData = $maintenance->get($machine->id);
            $inspection = $inspections->get($machine->id);
            $schedule = $schedules->get($machine->id);

            return (object) [
                'kode_mesin' => $machine->kode_mesin,
                'nama_mesin' => $machine->nama_mesin,
                'status_kondisi' => $machine->status_kondisi,
                'jumlah_shift' => (int) ($operation->jumlah_shift ?? 0),
                'rata_eff' => (float) ($operation->rata_eff ?? 0),
                'total_break' => (int) ($operation->total_break ?? 0),
                'jumlah_kerusakan' => (int) ($maintenanceData->jumlah_kerusakan ?? 0),
                'downtime_menit' => (int) ($maintenanceData->downtime_menit ?? 0),
                'pekerjaan_terbuka' => (int) ($maintenanceData->pekerjaan_terbuka ?? 0),
                'jumlah_inspeksi' => (int) ($inspection->jumlah_inspeksi ?? 0),
                'posisi_bermasalah' => (int) ($inspection->posisi_bermasalah ?? 0),
                'pm_selesai' => (int) ($schedule->pm_selesai ?? 0),
                'pm_terlambat' => (int) ($schedule->pm_terlambat ?? 0),
            ];
        });

        $maintenanceLogs = DB::table('log_maintenance_harian as log')
            ->join('mesin', 'log.mesin_id', '=', 'mesin.id')
            ->leftJoin('users as reporter', 'log.teknisi_id', '=', 'reporter.id')
            ->leftJoin('users as handler', 'log.ditangani_oleh', '=', 'handler.id')
            ->whereIn('log.mesin_id', $machineIds)
            ->whereBetween('log.tanggal', [$from, $to])
            ->when(!empty($filters['mesin_id']), fn ($query) => $query->where('log.mesin_id', $filters['mesin_id']))
            ->orderByDesc('log.tanggal')->orderByDesc('log.id')
            ->select('log.tanggal', 'mesin.kode_mesin', 'log.posisi_mesin', 'log.area_kerusakan',
                'log.problem', 'log.action', 'log.tindak_lanjut', 'log.status_perbaikan', 'log.downtime_menit',
                DB::raw('COALESCE(handler.nama_lengkap, reporter.nama_lengkap) as nama_teknisi'))
            ->paginate(15)->withQueryString();

        return view('reports.machines', compact('machineOptions', 'rows', 'maintenanceLogs', 'from', 'to', 'filters'));
    }
}
