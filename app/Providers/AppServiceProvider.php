<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function (ViewInstance $view): void {
            $newEscalationCount = 0;
            if (session('role') === 'teknisi2'
                && in_array(session('bidang_teknis'), ['elektrik', 'instrumen'], true)) {
                $newEscalationCount = DB::table('log_maintenance_harian')
                    ->where('area_kerusakan', session('bidang_teknis'))
                    ->where('status_eskalasi', 'sudah_dikirim')
                    ->count();
            }

            $view->with('newEscalationCount', $newEscalationCount);
        });
    }
}