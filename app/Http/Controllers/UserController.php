<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private function validatedUserData(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(['manajemen', 'teknisi1', 'teknisi2'])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'bidang_teknis' => ['nullable', 'required_if:role,teknisi2', Rule::in(['elektrik', 'instrumen'])],
        ]);

        if ($data['role'] === 'teknisi2') {
            $data['asal_tim'] = 'indorama';
        } elseif ($data['role'] === 'teknisi1') {
            $data['asal_tim'] = 'dty';
            $data['bidang_teknis'] = 'mekanik';
        } else {
            $data['asal_tim'] = 'dty';
            $data['bidang_teknis'] = null;
        }

        return $data;
    }

    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('nama_lengkap')->paginate(15)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedUserData($request);
        $password = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $data['password'] = Hash::make($password['password']);
        User::create($data);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedUserData($request, $user);
        $password = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (filled($password['password'] ?? null)) {
            $data['password'] = Hash::make($password['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }
}