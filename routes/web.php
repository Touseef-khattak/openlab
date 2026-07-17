<?php

use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ResultEntryController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\WorklistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::middleware('can:patients.view')->group(function () {
        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search');
    });
    Route::middleware('can:patients.manage')->group(function () {
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
        Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    });

    Route::get('doctors', [DoctorController::class, 'index'])
        ->middleware('can:doctors.view')->name('doctors.index');
    Route::middleware('can:doctors.manage')->group(function () {
        Route::post('doctors', [DoctorController::class, 'store'])->name('doctors.store');
        Route::put('doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');
    });

    Route::middleware('can:visits.view')->group(function () {
        Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
        Route::get('visits/{visit}', [VisitController::class, 'show'])->whereNumber('visit')->name('visits.show');
    });
    Route::middleware('can:visits.manage')->group(function () {
        Route::get('visits/create', [VisitController::class, 'create'])->name('visits.create');
        Route::post('visits', [VisitController::class, 'store'])->name('visits.store');
    });

    Route::middleware('can:results.enter')->group(function () {
        Route::get('worklist', [WorklistController::class, 'index'])->name('worklist.index');
        Route::post('samples/{sample}/collect', [SampleController::class, 'collect'])->name('samples.collect');
        Route::get('samples/{sample}/entry', [ResultEntryController::class, 'show'])->name('samples.entry');
        Route::put('samples/{sample}/results', [ResultEntryController::class, 'update'])->name('samples.results.update');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
