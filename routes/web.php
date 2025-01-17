<?php

use App\Livewire\Checkpoint\EditCheckpoint;
use App\Livewire\Checkpoint\IndexCheckpoint;
use App\Livewire\Checkpoint\ManageCheckpoint;
use App\Livewire\Employee\CreateEmployee;
use App\Livewire\Employee\EditEmployee;
use App\Livewire\Employee\IndexEmployee;
use App\Livewire\Employee\RegisterBiometric;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::prefix('checkpoints')->as('checkpoints.')->group(function () {
        Route::get('/', IndexCheckpoint::class)->name('index');
        Route::get('/edit/{checkpoint}', EditCheckpoint::class)->name('edit');
        Route::get('/{checkpoint}/manage', ManageCheckpoint::class)->name('manage');
        Route::get('/{checkpoint}/register-biometric', RegisterBiometric::class)->name('register-biometric');
    });

    Route::prefix('employees')->as('employees.')->group(function () {
        Route::get('/', IndexEmployee::class)->name('index');
        Route::get('/create', CreateEmployee::class)->name('create');
        Route::get('/edit/{employee}', EditEmployee::class)->name('edit');
    });
});

require __DIR__.'/auth.php';
