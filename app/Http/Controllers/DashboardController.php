<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $kpiMesin = Machine::query()->selectRaw(
            "COUNT(*) as total_mesin,
             SUM(status_kondisi = 'running') as total_running,
             SUM(status_kondisi = 'breakdown') as total_breakdown,
             SUM(status_kondisi = 'maintenance') as total_maintenance,
             SUM(status_kondisi = 'idle') as total_idle"
        )->first();

        $jadwalList = MaintenanceSchedule::with(['machine', 'technician'])
            ->whereIn('status', ['terjadwal', 'proses'])
            ->orderBy('tanggal_rencana')->limit(5)->get();
        $jadwalList->each(function (MaintenanceSchedule $schedule): void {
            if (in_array($schedule->status, ['terjadwal', 'proses'], true) && $schedule->tanggal_rencana->isBefore(today())) {
                $schedule->status = 'terlambat';
            }
        });
        $overdueSchedules = MaintenanceSchedule::query()
            ->whereIn('status', ['terjadwal', 'proses'])
            ->whereDate('tanggal_rencana', '<', today())
            ->count();

        $latestRoutinePmDates = DB::table('jadwal_maintenance')
            ->where('tipe_maintenance', 'rutin_2bulan')
            ->where('status', 'selesai')
            ->whereNotNull('tanggal_realisasi')
            ->selectRaw('mesin_id, MAX(tanggal_realisasi) as tanggal_pm_terakhir')
            ->groupBy('mesin_id')
            ->pluck('tanggal_pm_terakhir', 'mesin_id');
        $plannedRoutinePmDates = DB::table('jadwal_maintenance')
            ->where('tipe_maintenance', 'rutin_2bulan')
            ->whereIn('status', ['terjadwal', 'proses'])
            ->selectRaw('mesin_id, MIN(tanggal_rencana) as tanggal_pm_rencana')
            ->groupBy('mesin_id')
            ->pluck('tanggal_pm_rencana', 'mesin_id');
        $routinePmReminders = Machine::query()
            ->whereIn('id', $latestRoutinePmDates->keys())
            ->orderBy('kode_mesin')
            ->get(['id', 'kode_mesin', 'nama_mesin'])
            ->map(function (Machine $machine) use ($latestRoutinePmDates, $plannedRoutinePmDates): object {
                $lastCompleted = Carbon::parse($latestRoutinePmDates->get($machine->id));
                $dueDate = $lastCompleted->copy()->addMonthsNoOverflow(2);
                $plannedDate = $plannedRoutinePmDates->get($machine->id);
                $plannedDate = $plannedDate ? Carbon::parse($plannedDate) : null;

                return (object) [
                    'kode_mesin' => $machine->kode_mesin,
                    'nama_mesin' => $machine->nama_mesin,
                    'tanggal_pm_terakhir' => $lastCompleted,
                    'tanggal_jatuh_tempo' => $dueDate,
                    'sudah_dijadwalkan' => $plannedDate
                        && $plannedDate->greaterThanOrEqualTo($lastCompleted)
                        && $plannedDate->lessThanOrEqualTo($dueDate),
                ];
            })
            ->reject(fn (object $reminder): bool => $reminder->sudah_dijadwalkan)
            ->sortBy('tanggal_jatuh_tempo')
            ->values();

        $currentUser = User::findOrFail(session('user_id'));
        $showSchedules = $currentUser->role === 'manajemen'
            || $currentUser->role === 'teknisi1';
        $canCompleteLogs = $currentUser->role === 'manajemen'
            || $currentUser->role === 'teknisi1';
        $recentLogsQuery = MaintenanceLog::with(['machine', 'technician', 'handler', 'spareParts']);

        if ($currentUser->role === 'teknisi1') {
            $recentLogsQuery->where('area_kerusakan', 'mekanik');
        } elseif ($currentUser->role === 'teknisi2') {
            $recentLogsQuery->where('area_kerusakan', $currentUser->bidang_teknis);
            $recentLogsQuery->whereIn('status_eskalasi', ['sudah_dikirim', 'ditangani']);
        }

        $recentLogs = $recentLogsQuery->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get();

        $breakdownSummary = MaintenanceLog::query()->whereDate('tanggal', today())
            ->selectRaw('COUNT(id) as total_breakdown, COALESCE(SUM(downtime_menit), 0) as downtime_menit')
            ->first();

        $pendingEscalations = MaintenanceLog::query()
            ->whereIn('status_eskalasi', ['perlu_dikirim', 'sudah_dikirim'])->count();

        return view('dashboard', compact(
            'kpiMesin', 'jadwalList', 'recentLogs', 'overdueSchedules',
            'breakdownSummary', 'pendingEscalations', 'currentUser', 'showSchedules', 'canCompleteLogs',
            'routinePmReminders'
        ));
    }
}