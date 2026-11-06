<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceLogRequest;
use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceLogController extends Controller
{
    public function index(): View
    {
        $user = User::findOrFail(session('user_id'));
        $query = MaintenanceLog::with(['machine', 'technician', 'handler', 'spareParts']);

        if ($user->role === 'teknisi1') {
            $query->where('area_kerusakan', 'mekanik');
        } elseif ($user->role === 'teknisi2') {
            $query->where('area_kerusakan', $user->bidang_teknis)
                ->whereIn('status_eskalasi', ['sudah_dikirim', 'ditangani']);
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15);
        $canCreate = $user->role === 'manajemen';
        $canComplete = $user->role === 'manajemen' || $user->role === 'teknisi1';

        return view('maintenance.logs.index', compact('logs', 'user', 'canCreate', 'canComplete'));
    }

    public function create(): View
    {
        $user = User::findOrFail(session('user_id'));
        abort_unless($user->role === 'manajemen', 403);
        $machines = Machine::orderBy('kode_mesin')->get();
        $areas = ['mekanik', 'elektrik', 'instrumen', 'lainnya'];

        return view('maintenance.logs.create', compact('machines', 'areas'));
    }

    public function store(StoreMaintenanceLogRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $spareParts = $validated['spare_parts'] ?? [];
        unset($validated['spare_parts']);

        $validated['downtime_menit'] = null;
        $validated['status_perbaikan'] = 'proses';
        $needsIndorama = in_array($validated['area_kerusakan'], ['elektrik', 'instrumen'], true);
        $validated['status_eskalasi'] = $needsIndorama ? 'perlu_dikirim' : 'tidak_perlu';
        if ($needsIndorama) {
            $validated['eskalasi_ke'] = 'Tim Indorama - ' . $validated['area_kerusakan'];
        } else {
            $validated['eskalasi_ke'] = null;
        }

        DB::transaction(function () use ($validated, $spareParts): void {
            $log = MaintenanceLog::create($validated + ['teknisi_id' => session('user_id')]);
            $log->spareParts()->createMany($spareParts);

            if ($log->status_perbaikan !== 'selesai') {
                Machine::whereKey($log->mesin_id)->update(['status_kondisi' => 'breakdown']);
            }
        });

        return redirect()->route('maintenance.logs')->with('success', 'Log maintenance dan spare part berhasil disimpan.');
    }

    public function sendEscalation(int $id): RedirectResponse
    {
        $user = User::findOrFail(session('user_id'));
        $log = MaintenanceLog::findOrFail($id);
        abort_unless($user->role === 'manajemen', 403);
        abort_unless(in_array($log->area_kerusakan, ['elektrik', 'instrumen'], true), 422);
        abort_unless($log->status_eskalasi === 'perlu_dikirim', 409);

        $log->update(['status_eskalasi' => 'sudah_dikirim']);

        return back()->with('success', 'Eskalasi dikirim ke tim Indorama bidang ' . $log->area_kerusakan . '.');
    }

    public function resolveEscalation(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'tindak_lanjut' => ['required', 'string', 'max:5000'],
        ]);
        $user = User::findOrFail(session('user_id'));
        abort_unless($user->role === 'teknisi2', 403);

        $log = MaintenanceLog::whereKey($id)
            ->where('area_kerusakan', $user->bidang_teknis)
            ->where('status_eskalasi', 'sudah_dikirim')
            ->firstOrFail();

        $start = Carbon::parse($log->tanggal->format('Y-m-d') . ' ' . $log->waktu_mulai);
        $end = now()->startOfMinute();
        if ($end->lessThan($start)) {
            return back()->withErrors([
                'tindak_lanjut' => 'Waktu mulai tercatat setelah waktu sekarang. Periksa tanggal dan jam mulai sebelum menyelesaikan tiket.',
            ])->withInput();
        }

        DB::transaction(function () use ($log, $user, $data, $end, $start): void {
            $log->update([
                'status_eskalasi' => 'ditangani',
                'tindak_lanjut' => $data['tindak_lanjut'],
                'ditangani_oleh' => $user->id,
                'status_perbaikan' => 'selesai',
                'waktu_selesai' => $end->format('H:i'),
                'downtime_menit' => (int) $start->diffInMinutes($end),
            ]);
            $this->restoreMachineAfterRepair($log->mesin_id);
        });

        return back()->with('success', 'Tindak lanjut tim Indorama berhasil dicatat.');
    }

    public function completeLog(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'tindak_lanjut' => ['required', 'string', 'max:5000'],
        ]);
        $user = User::findOrFail(session('user_id'));
        $isManager = $user->role === 'manajemen';
        $isDtyMechanic = $user->role === 'teknisi1';
        abort_unless($isManager || $isDtyMechanic, 403);

        $log = MaintenanceLog::whereKey($id)
            ->where('area_kerusakan', 'mekanik')
            ->where('status_perbaikan', 'proses')
            ->where('status_eskalasi', 'tidak_perlu')
            ->firstOrFail();

        $start = Carbon::parse($log->tanggal->format('Y-m-d') . ' ' . $log->waktu_mulai);
        $completedAt = now()->startOfMinute();
        abort_if($completedAt->lessThan($start), 422, 'Waktu mulai tidak boleh setelah waktu penyelesaian.');

        DB::transaction(function () use ($log, $user, $data, $completedAt, $start): void {
            $log->update([
                'status_perbaikan' => 'selesai',
                'waktu_selesai' => $completedAt->format('H:i'),
                'downtime_menit' => (int) $start->diffInMinutes($completedAt),
                'ditangani_oleh' => $user->id,
                'tindak_lanjut' => $data['tindak_lanjut'],
            ]);
            $this->restoreMachineAfterRepair($log->mesin_id);
        });

        return back()->with('success', 'Perbaikan ditandai selesai dan downtime berhasil dicatat.');
    }

    private function restoreMachineAfterRepair(int $machineId): void
    {
        $hasOpenRepair = MaintenanceLog::where('mesin_id', $machineId)
            ->where('status_perbaikan', '<>', 'selesai')
            ->exists();
        if ($hasOpenRepair) {
            return;
        }

        Machine::whereKey($machineId)
            ->where('status_kondisi', 'breakdown')
            ->update(['status_kondisi' => 'running']);
    }
}