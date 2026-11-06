<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMaintenanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = User::find($this->session()->get('user_id'));

        return $user?->role === 'manajemen';
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'mesin_id' => ['required', 'integer', 'exists:mesin,id'],
            'posisi_mesin' => ['required', 'string', 'max:50'],
            'status_idle' => ['required', Rule::in(['Permanen', 'Non-Permanen', 'None'])],
            'problem' => ['required', 'string'],
            'action' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'area_kerusakan' => ['required', Rule::in(['mekanik', 'elektrik', 'instrumen', 'lainnya'])],
            'jumlah_posisi_terdampak' => ['required', 'integer', 'min:1', 'max:500'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'spare_parts' => ['nullable', 'array'],
            'spare_parts.*.nama_part' => ['required', 'string', 'max:120'],
            'spare_parts.*.nomor_part' => ['nullable', 'string', 'max:60'],
            'spare_parts.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'spare_parts.*.satuan' => ['required', 'string', 'max:20'],
            'spare_parts.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'spare_parts.*.keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
        }];
    }
}