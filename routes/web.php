<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::get('checkpoints', \App\Livewire\Checkpoint\IndexCheckpoint::class)
        ->name('checkpoints.index');

    Route::get('checkpoints/edit/{checkpoint}', \App\Livewire\Checkpoint\EditCheckpoint::class)
        ->name('checkpoints.edit');

    Route::get('checkpoints/{checkpoint}/register-biometric', \App\Livewire\Employee\RegisterBiometric::class)
        ->name('checkpoints.register-biometric');

    Route::prefix('employees')->as('employees.')->group(function () {
        Route::get('/', \App\Livewire\Employee\IndexEmployee::class)->name('index');
        Route::get('/create', \App\Livewire\Employee\CreateEmployee::class)->name('create');
        Route::get('/edit/{employee}', \App\Livewire\Employee\EditEmployee::class)->name('edit');
    });
});

require __DIR__.'/auth.php';
