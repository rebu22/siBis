<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

// --- Publik (RF-01..RF-04) ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/kebijakan', [RegulationController::class, 'index'])->name('regulations.index');
Route::get('/kebijakan/{regulation}', [RegulationController::class, 'show'])->name('regulations.show');

// --- Bantuan (RF-08, RF-09) ---
Route::get('/lapor-bug', [SupportController::class, 'bugForm'])->name('bug.create');
Route::post('/lapor-bug', [SupportController::class, 'bugStore'])->middleware('throttle:5,1')->name('bug.store');

Route::middleware('auth')->group(function () {
    Route::get('/hubungi-admin', [SupportController::class, 'contactForm'])->name('contact.create');
    Route::post('/hubungi-admin', [SupportController::class, 'contactStore'])->middleware('throttle:5,1')->name('contact.store');
});

// --- Konsultasi AI (RF-05..RF-13, RF-25, RF-26) ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/konsultasi', [ConsultationController::class, 'index'])->name('consultation.index');
    Route::post('/konsultasi', [ConsultationController::class, 'ask'])->middleware('throttle:consult')->name('consultation.ask');

    Route::get('/riwayat', [ConsultationController::class, 'history'])->name('history.index');
    Route::get('/riwayat/{session}', [ConsultationController::class, 'show'])->name('history.show');
    Route::delete('/riwayat', [ConsultationController::class, 'destroyAll'])->name('history.destroyAll');
    Route::delete('/riwayat/{session}', [ConsultationController::class, 'destroy'])->name('history.destroy');

    Route::post('/jawaban/{message}/nilai', [ConsultationController::class, 'rate'])->name('answers.rate');
    Route::post('/jawaban/{message}/lapor', [ConsultationController::class, 'report'])->name('answers.report');

    Route::get('/dashboard', fn () => redirect()->route('consultation.index'))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- Admin (UC-03) ---
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/pengguna', [AdminController::class, 'users'])->name('admin.users');

    Route::get('/profil', [AdminController::class, 'editProfile'])->name('admin.profile.edit');
    Route::patch('/profil', [AdminController::class, 'updateProfile'])->name('admin.profile.update');

    Route::get('/laporan', [AdminController::class, 'reports'])->name('admin.reports');
    Route::patch('/laporan/{report}', [AdminController::class, 'updateReport'])->name('admin.reports.update');

    Route::get('/audit', [AdminController::class, 'audit'])->name('admin.audit');

    Route::get('/laporan-bug', [AdminController::class, 'bugReports'])->name('admin.bugs');
    Route::patch('/laporan-bug/{bugReport}', [AdminController::class, 'updateBugReport'])->name('admin.bugs.update');

    Route::get('/pesan', [AdminController::class, 'messages'])->name('admin.messages');
    Route::patch('/pesan/{message}', [AdminController::class, 'updateMessage'])->name('admin.messages.update');

    Route::get('/kebijakan', [AdminController::class, 'regulations'])->name('admin.regulations');
    Route::post('/kebijakan', [AdminController::class, 'storeRegulation'])->name('admin.regulations.store');
    Route::post('/kebijakan/{regulation}/indeks', [AdminController::class, 'reindexRegulation'])->name('admin.regulations.reindex');
    Route::patch('/kebijakan/{regulation}', [AdminController::class, 'updateRegulation'])->name('admin.regulations.update');
    Route::delete('/kebijakan/{regulation}', [AdminController::class, 'destroyRegulation'])->name('admin.regulations.destroy');
    Route::get('/verifikasi', [AdminController::class, 'showTwoFactor'])->name('admin.2fa');
    Route::post('/verifikasi/kirim', [AdminController::class, 'sendOtp'])->middleware('throttle:6,1')->name('admin.2fa.send');
    Route::post('/verifikasi', [AdminController::class, 'verifyOtp'])->name('admin.2fa.verify');
});

require __DIR__.'/auth.php';
