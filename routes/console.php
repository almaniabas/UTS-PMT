<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function (): void {
    $this->comment('Sistem Manajemen Mesin dan Maintenance DTY');
})->purpose('Menampilkan informasi aplikasi');