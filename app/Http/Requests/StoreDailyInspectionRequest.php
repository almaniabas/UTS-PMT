<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDailyInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->get('role') === 'manajemen';
    }

    public function rules(): array
    {
        return [
            'mesin_id' => ['required', 'integer', 'exists:mesin,id'],
            'tanggal' => ['required', 'date'],
            'total_posisi' => ['required', 'integer', 'min:1', 'max:500'],
            'keputusan_mesin' => ['required', Rule::in(['running', 'stop'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'positions' => ['nullable', 'array'],
            'positions.*.nomor_posisi' => ['required', 'integer', 'min:1', 'distinct'],
            'positions.*.jenis_problem' => ['required', Rule::in(['tension_tinggi', 'tension_rendah', 'broken_filament', 'cross_winding', 'over_diameter', 'kilap', 'lainnya'])],
            'positions.*.catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $positions = $this->input('positions', []);
            $total = (int) $this->input('total_posisi', 0);

            foreach ($positions as $index => $position) {
                if ((int) ($position['nomor_posisi'] ?? 0) > $total) {
                    $validator->errors()->add("positions.{$index}.nomor_posisi", 'Nomor posisi melebihi jumlah spindle mesin.');
                }
            }

            $machineTotal = \App\Models\Machine::whereKey($this->input('mesin_id'))->value('jumlah_posisi');
            if ($machineTotal && $total !== (int) $machineTotal) {
                $validator->errors()->add('total_posisi', 'Jumlah posisi harus sesuai dengan data mesin.');
            }
        }];
    }
}