<?php

use App\Http\Controllers\PortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyInspectionController;
use App\Http\Controllers\MachineReportController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [PortalController::class, 'login'])->name('login');
Route::post('/login', [PortalController::class, 'authenticate'])->name('login.store');
Route::post('/logout', [PortalController::class, 'logout'])->name('logout');

Route::middleware('role:manajemen,teknisi1,teknisi2')->group(function (): void {
	Route::get('/dashboard', DashboardController::class)->name('dashboard');
	Route::get('/laporan-mesin', [MachineReportController::class, 'index'])->name('machine-reports.index');
	Route::get('/mesin', [PortalController::class, 'machines'])->name('machines.index');
	Route::get('/maintenance', [PortalController::class, 'schedules'])->name('maintenance.index');
	Route::get('/maintenance/log', [MaintenanceLogController::class, 'index'])->name('maintenance.logs');
	Route::post('/maintenance/log/{id}/resolve-escalation', [MaintenanceLogController::class, 'resolveEscalation'])->name('maintenance.logs.resolve-escalation');
	Route::post('/maintenance/log/{id}/complete', [MaintenanceLogController::class, 'completeLog'])->name('maintenance.logs.complete');
});

Route::middleware('role:manajemen')->group(function (): void {
	Route::get('/maintenance/log/create', [MaintenanceLogController::class, 'create'])->name('maintenance.logs.create');
	Route::post('/maintenance/log', [MaintenanceLogController::class, 'store'])->name('maintenance.logs.store');
	Route::post('/maintenance/log/{id}/send-escalation', [MaintenanceLogController::class, 'sendEscalation'])->name('maintenance.logs.send-escalation');
	Route::get('/users', [UserController::class, 'index'])->name('users.index');
	Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
	Route::post('/users', [UserController::class, 'store'])->name('users.store');
	Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
	Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
	Route::get('/mesin/create', [PortalController::class, 'machineCreate'])->name('machines.create');
	Route::post('/mesin', [PortalController::class, 'machineStore'])->name('machines.store');
	Route::get('/mesin/{id}/edit', [PortalController::class, 'machineEdit'])->name('machines.edit');
	Route::put('/mesin/{id}', [PortalController::class, 'machineUpdate'])->name('machines.update');
});

Route::middleware('role:teknisi1,manajemen')->group(function (): void {
	Route::get('/maintenance/create', [PortalController::class, 'scheduleCreate'])->name('maintenance.create');
	Route::post('/maintenance', [PortalController::class, 'scheduleStore'])->name('maintenance.store');
	Route::post('/maintenance/{id}/complete', [PortalController::class, 'scheduleComplete'])->name('maintenance.complete');
});

Route::middleware('role:manajemen')->group(function (): void {
	Route::get('/inspeksi-harian', [DailyInspectionController::class, 'index'])->name('inspections.index');
	Route::get('/inspeksi-harian/create', [DailyInspectionController::class, 'create'])->name('inspections.create');
	Route::post('/inspeksi-harian', [DailyInspectionController::class, 'store'])->name('inspections.store');
	Route::get('/operasional', [PortalController::class, 'operations'])->name('operations.index');
	Route::get('/operasional/create', [PortalController::class, 'operationCreate'])->name('operations.create');
	Route::post('/operasional', [PortalController::class, 'operationStore'])->name('operations.store');
});