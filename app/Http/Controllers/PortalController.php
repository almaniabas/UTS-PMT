<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalController extends Controller
{
    private function form(string $type, string $title, mixed $record = null): View
    {
        return view('shared.form', [
            'formType' => $type,
            'title' => $title,
            'record' => $record,
            'machines' => DB::table('mesin')->orderBy('kode_mesin')->get(),
            'technicians' => DB::table('users')->where('role', 'teknisi1')
                ->where('status', 'aktif')->orderBy('nama_lengkap')->get(),
        ]);
    }

    private function listing(string $title, string $description, array $columns, mixed $rows, string $type): View
    {
        return view('shared.index', compact('title', 'description', 'columns', 'rows', 'type'));
    }

    public function login(): View|RedirectResponse
    {
        if (session()->has('user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users')
            ->where('username', $credentials['username'])
            ->where('status', 'aktif')
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Username atau password salah.');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'user_id' => $user->id,
            'username' => $user->username,
            'nama_lengkap' => $user->nama_lengkap,
            'role' => $user->role,
                'asal_tim' => $user->asal_tim ?? null,
                'bidang_teknis' => $user->bidang_teknis ?? null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Selamat datang, ' . $user->nama_lengkap . '.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    public function machines(Request $request): View
    {
        $query = DB::table('mesin')->orderBy('kode_mesin');
        if ($request->filled('status')) {
            $query->where('status_kondisi', $request->string('status')->toString());
        }
        $machines = $query->get();
        $machineIds = $machines->pluck('id');
        $openLogs = DB::table('log_maintenance_harian')
            ->whereIn('mesin_id', $machineIds)
            ->where('status_perbaikan', '<>', 'selesai')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('mesin_id')
            ->map(fn ($logs) => $logs->first());
        $latestInspections = DB::table('inspeksi_harian')
            ->whereIn('mesin_id', $machineIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('mesin_id')
            ->map(fn ($inspections) => $inspections->first());

        $machines->each(function ($machine) use ($openLogs, $latestInspections): void {
            $machine->status_reason = '-';
            if ($machine->status_kondisi !== 'breakdown') {
                return;
            }

            $openLog = $openLogs->get($machine->id);
            if ($openLog) {
                $machine->status_reason = ucfirst($openLog->area_kerusakan ?? 'Kerusakan')
                    . ': ' . \Illuminate\Support\Str::limit($openLog->problem, 100);
                return;
            }

            $inspection = $latestInspections->get($machine->id);
            if ($inspection?->keputusan_mesin === 'stop') {
                $machine->status_reason = 'Inspeksi spindle: '
                    . $inspection->posisi_bermasalah . ' posisi bermasalah ('
                    . \Illuminate\Support\Carbon::parse($inspection->tanggal)->format('d/m/Y') . ')';
                return;
            }

            $machine->status_reason = 'Belum ada catatan penyebab breakdown yang terkait.';
        });

        return $this->listing('Master Mesin DTY', 'Manajemen armada mesin, spesifikasi spindle, dan status operasional.', [
            'Kode' => 'kode_mesin', 'Nama Mesin' => 'nama_mesin', 'Merk / Tipe' => 'merk_tipe',
            'Posisi' => 'jumlah_posisi', 'Tahun' => 'tahun_instalasi', 'Status' => 'status_kondisi',
            'Sumber / Penyebab' => 'status_reason',
        ], $machines, 'machines');
    }

    public function machineCreate(): View
    {
        return $this->form('machine', 'Tambah Mesin');
    }

    public function machineEdit(int $id): View
    {
        return $this->form('machine', 'Edit Mesin', DB::table('mesin')->find($id) ?? abort(404));
    }

    public function machineStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_mesin' => ['required', 'string', 'max:30', 'unique:mesin,kode_mesin'],
            'nama_mesin' => ['required', 'string', 'max:100'],
            'merk_tipe' => ['nullable', 'string', 'max:100'],
            'jumlah_posisi' => ['required', 'integer', 'min:1'],
            'tahun_instalasi' => ['nullable', 'integer', 'between:1900,2100'],
            'status_kondisi' => ['required', Rule::in(['running', 'breakdown', 'maintenance', 'idle'])],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::table('mesin')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('machines.index')->with('success', 'Mesin berhasil ditambahkan. Jadwal maintenance dapat dibuat melalui menu Jadwal.');
    }

    public function machineUpdate(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'kode_mesin' => ['required', 'string', 'max:30', Rule::unique('mesin', 'kode_mesin')->ignore($id)],
            'nama_mesin' => ['required', 'string', 'max:100'],
            'merk_tipe' => ['nullable', 'string', 'max:100'],
            'jumlah_posisi' => ['required', 'integer', 'min:1'],
            'tahun_instalasi' => ['nullable', 'integer', 'between:1900,2100'],
            'status_kondisi' => ['required', Rule::in(['running', 'breakdown', 'maintenance', 'idle'])],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::table('mesin')->where('id', $id)->update($data + ['updated_at' => now()]);

        return redirect()->route('machines.index')->with('success', 'Data mesin berhasil diperbarui.');
    }

    public function schedules(): View
    {
        $this->ensureScheduleAccess();

        $lastRoutinePmByMachine = DB::table('jadwal_maintenance')
            ->where('tipe_maintenance', 'rutin_2bulan')
            ->where('status', 'selesai')
            ->whereNotNull('tanggal_realisasi')
            ->selectRaw('mesin_id, MAX(tanggal_realisasi) as tanggal_pm_terakhir')
            ->groupBy('mesin_id')
            ->pluck('tanggal_pm_terakhir', 'mesin_id');

        $rows = DB::table('jadwal_maintenance as jm')->join('mesin as m', 'jm.mesin_id', '=', 'm.id')
            ->leftJoin('users as u', 'jm.teknisi_id', '=', 'u.id')
            ->orderBy('jm.tanggal_rencana')
            ->select('jm.*', 'm.kode_mesin', 'u.nama_lengkap as nama_teknisi')->get();
        $rows->each(function ($schedule): void {
            if (in_array($schedule->status, ['terjadwal', 'proses'], true)
                && $schedule->tanggal_rencana < today()->toDateString()) {
                $schedule->status = 'terlambat';
            }
        });
        $rows->each(function ($schedule) use ($lastRoutinePmByMachine): void {
            $lastCompleted = $lastRoutinePmByMachine->get($schedule->mesin_id);
            $schedule->tanggal_pm_rutin_berikutnya = $lastCompleted
                ? \Illuminate\Support\Carbon::parse($lastCompleted)->addMonthsNoOverflow(2)->toDateString()
                : null;
        });

        return $this->listing('Jadwal Maintenance', 'Jadwal rutin dan inspeksi mesin yang dibuat sesuai kebutuhan.', [
            'Mesin' => 'kode_mesin', 'Rencana' => 'tanggal_rencana', 'Realisasi' => 'tanggal_realisasi',
            'Jatuh Tempo PM Rutin' => 'tanggal_pm_rutin_berikutnya', 'Hasil / Spare Part Diganti' => 'catatan_realisasi',
            'Tipe' => 'tipe_maintenance', 'Teknisi' => 'nama_teknisi', 'Status' => 'status',
        ], $rows, 'schedules');
    }

    public function scheduleCreate(): View
    {
        $this->ensureScheduleAccess();

        return $this->form('schedule', 'Buat Jadwal Maintenance');
    }

    public function scheduleStore(Request $request): RedirectResponse
    {
        $this->ensureScheduleAccess();

        $data = $request->validate([
            'mesin_id' => ['required', 'exists:mesin,id'],
            'tanggal_rencana' => ['required', 'date'],
            'tipe_maintenance' => ['required', Rule::in(['rutin_2bulan', 'overhaul', 'inspeksi_khusus'])],
            'teknisi_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('role', 'teknisi1'))],
            'catatan_perawatan' => ['nullable', 'string'],
        ]);
        DB::table('jadwal_maintenance')->insert($data + ['status' => 'terjadwal', 'created_at' => now()]);

        return redirect()->route('maintenance.index')->with('success', 'Jadwal maintenance berhasil dibuat.');
    }

    public function scheduleComplete(Request $request, int $id): RedirectResponse
    {
        $this->ensureScheduleAccess();
        $data = $request->validate([
            'catatan_realisasi' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($id, $data): void {
            $schedule = DB::table('jadwal_maintenance')->where('id', $id)->lockForUpdate()->first();
            abort_unless($schedule, 404);
            abort_unless(in_array($schedule->status, ['terjadwal', 'proses'], true), 409);

            DB::table('jadwal_maintenance')->where('id', $id)->update([
                'status' => 'selesai',
                'tanggal_realisasi' => today()->toDateString(),
                'selesai_pada' => now(),
                'catatan_realisasi' => $data['catatan_realisasi'] ?? null,
            ]);

            DB::table('mesin')->where('id', $schedule->mesin_id)->where('status_kondisi', 'maintenance')
                ->update(['status_kondisi' => 'running', 'updated_at' => now()]);
        });

        return redirect()->route('maintenance.index')
            ->with('success', 'Jadwal ditandai selesai dan dipindahkan ke daftar Sudah Selesai.');
    }

    private function ensureScheduleAccess(): void
    {
        $user = DB::table('users')->where('id', session('user_id'))->first();
        $allowed = $user && in_array($user->role, ['manajemen', 'teknisi1'], true);

        abort_unless($allowed, 403);
    }

    public function maintenanceLogs(): View
    {
        $rows = DB::table('log_maintenance_harian as lm')->join('mesin as m', 'lm.mesin_id', '=', 'm.id')
            ->leftJoin('users as u', 'lm.teknisi_id', '=', 'u.id')->orderByDesc('lm.tanggal')
            ->select('lm.*', 'm.kode_mesin', 'u.nama_lengkap as nama_teknisi')->get();

        return $this->listing('Log Catatan Buku', 'Catatan kendala, tindakan perbaikan, dan status pekerjaan teknisi.', [
            'Tanggal' => 'tanggal', 'Mesin' => 'kode_mesin', 'Posisi' => 'posisi_mesin',
            'Problem' => 'problem', 'Tindakan' => 'action', 'Teknisi' => 'nama_teknisi', 'Status' => 'status_perbaikan',
        ], $rows, 'maintenance-logs');
    }

    public function maintenanceLogCreate(): View
    {
        return $this->form('maintenance-log', 'Catat Log Maintenance');
    }

    public function maintenanceLogStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'], 'mesin_id' => ['required', 'exists:mesin,id'],
            'posisi_mesin' => ['required', 'string', 'max:50'],
            'status_idle' => ['required', Rule::in(['Permanen', 'Non-Permanen', 'None'])],
            'problem' => ['required', 'string'], 'action' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        $data['teknisi_id'] = session('user_id');
        $data['status_perbaikan'] = 'proses';

        DB::transaction(function () use ($data): void {
            DB::table('log_maintenance_harian')->insert($data + ['created_at' => now()]);
            if ($data['status_idle'] === 'Permanen' && $data['status_perbaikan'] !== 'selesai') {
                DB::table('mesin')->where('id', $data['mesin_id'])->update(['status_kondisi' => 'breakdown', 'updated_at' => now()]);
            }
        });

        return redirect()->route('maintenance.logs')->with('success', 'Log maintenance berhasil disimpan.');
    }

    public function operations(): View
    {
        $rows = DB::table('operasional_mesin as o')->join('mesin as m', 'o.mesin_id', '=', 'm.id')
            ->leftJoin('users as u', 'o.operator_id', '=', 'u.id')->orderByDesc('o.tanggal')->orderByDesc('o.shift')
            ->select('o.*', 'm.kode_mesin', 'u.nama_lengkap as nama_operator')->get();

        return $this->listing('Operasional Shift & EFF', 'Efisiensi, break, status idle, vessel, dan anomali per shift.', [
            'Tanggal' => 'tanggal', 'Shift' => 'shift', 'Mesin' => 'kode_mesin', 'EFF (%)' => 'eff_persen',
            'Total Break' => 'total_break', 'Idle' => 'status_idle', 'Jam' => 'jam_start', 'Material' => 'material',
        ], $rows, 'operations');
    }

    public function operationCreate(): View
    {
        return $this->form('operation', 'Input Log Operasional');
    }

    public function operationStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'], 'mesin_id' => ['required', 'exists:mesin,id'],
            'shift' => ['required', Rule::in(['Shift 1', 'Shift 2', 'Shift 3'])],
            'eff_persen' => ['required', 'numeric', 'between:0,100'],
            'no_dog_break' => ['nullable', 'string', 'max:50'], 'total_break' => ['required', 'integer', 'min:0'],
            'status_idle' => ['required', Rule::in(['None', 'Permanen', 'Non-Permanen'])],
            'vessel_status' => ['nullable', 'string', 'max:100'], 'anomali' => ['nullable', 'string'],
            'jam_start' => ['required', 'date_format:H:i'], 'jam_stop' => ['required', 'date_format:H:i'],
            'material' => ['required', 'string', 'max:100'],
        ]);
        $duplicate = DB::table('operasional_mesin')
            ->where('mesin_id', $data['mesin_id'])
            ->whereDate('tanggal', $data['tanggal'])
            ->where('shift', $data['shift'])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'shift' => 'Catatan untuk mesin, tanggal, dan shift tersebut sudah ada.',
            ]);
        }
        DB::table('operasional_mesin')->insert($data + ['operator_id' => session('user_id'), 'created_at' => now()]);

        return redirect()->route('operations.index')->with('success', 'Log operasional berhasil dicatat.');
    }

}