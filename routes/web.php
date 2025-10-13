<?php

use App\Livewire\WeightDashboard;
use App\Livewire\WorkoutDashboard;
use App\Livewire\WorkoutImportForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', WorkoutDashboard::class)->name('dashboard');
    Route::get('/import', WorkoutImportForm::class)->name('import');
    Route::get('/weight', WeightDashboard::class)->name('weight');
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
