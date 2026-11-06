<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyInspectionRequest;
use App\Models\DailyInspection;
use App\Models\Machine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DailyInspectionController extends Controller
{
    public function index(): View
    {
        $inspections = DailyInspection::with(['machine', 'inspector', 'positions'])
            ->latest('tanggal')->paginate(20);

        return view('inspections.index', compact('inspections'));
    }

    public function create(): View
    {
        return view('inspections.create', ['machines' => Machine::orderBy('kode_mesin')->get()]);
    }

    public function store(StoreDailyInspectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $positions = $data['positions'] ?? [];
        unset($data['positions']);

        $alreadyExists = DailyInspection::where('mesin_id', $data['mesin_id'])
            ->whereDate('tanggal', $data['tanggal'])->exists();
        if ($alreadyExists) {
            return back()->withInput()->withErrors(['tanggal' => 'Inspeksi mesin ini sudah dicatat untuk tanggal tersebut.']);
        }

        DB::transaction(function () use ($data, $positions): void {
            $inspection = DailyInspection::create($data + [
                'pc_id' => session('user_id'),
                'posisi_bermasalah' => count($positions),
            ]);

            $inspection->positions()->createMany(array_map(
                fn (array $position): array => $position + ['status' => 'problem'],
                $positions
            ));

            if ($inspection->keputusan_mesin === 'stop') {
                Machine::whereKey($inspection->mesin_id)->update(['status_kondisi' => 'breakdown']);
            } else {
                $hasOpenRepair = \App\Models\MaintenanceLog::where('mesin_id', $inspection->mesin_id)
                    ->where('status_perbaikan', '<>', 'selesai')
                    ->exists();
                if (!$hasOpenRepair) {
                    Machine::whereKey($inspection->mesin_id)
                        ->where('status_kondisi', 'breakdown')
                        ->update(['status_kondisi' => 'running']);
                }
            }
        });

        return redirect()->route('inspections.index')->with('success', 'Inspeksi harian berhasil disimpan.');
    }
}