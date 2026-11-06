<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!in_array($request->session()->get('role'), $roles, true)) {
            abort(403, 'Akun Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}