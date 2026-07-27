<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultEntryController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\WorklistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:patients.view')->group(function () {
        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search');
    });
    Route::middleware('can:patients.manage')->group(function () {
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
        Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    });

    Route::get('tests', [TestController::class, 'index'])
        ->middleware('can:catalog.view')->name('tests.index');
    Route::middleware('can:catalog.manage')->group(function () {
        Route::get('tests/create', [TestController::class, 'create'])->name('tests.create');
        Route::post('tests', [TestController::class, 'store'])->name('tests.store');
        Route::get('tests/{test}/edit', [TestController::class, 'edit'])->whereNumber('test')->name('tests.edit');
        Route::put('tests/{test}', [TestController::class, 'update'])->whereNumber('test')->name('tests.update');
        Route::patch('tests/{test}/toggle', [TestController::class, 'toggle'])->whereNumber('test')->name('tests.toggle');
        Route::delete('tests/{test}', [TestController::class, 'destroy'])->whereNumber('test')->name('tests.destroy');
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

    Route::middleware('can:results.verify')->group(function () {
        Route::get('verification', [VerificationController::class, 'index'])->name('verification.index');
        Route::get('samples/{sample}/verify', [VerificationController::class, 'show'])->name('samples.verify.show');
        Route::post('samples/{sample}/verify', [VerificationController::class, 'verify'])->name('samples.verify');
        Route::post('samples/{sample}/reject', [VerificationController::class, 'reject'])->name('samples.reject');
    });

    Route::get('visits/{visit}/report', [ReportController::class, 'labReport'])
        ->whereNumber('visit')
        ->middleware('can:reports.view')
        ->name('visits.report');

    // Business / MIS reports (admin — reports.business)
    Route::middleware('can:reports.business')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/daily-collection', [ReportController::class, 'dailyCollection'])->name('reports.daily-collection');
        Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('reports/volume-tat', [ReportController::class, 'volumeTat'])->name('reports.volume-tat');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
