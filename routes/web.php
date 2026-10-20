<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Publik (RF-01..RF-04) ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/artikel', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/artikel/{article}', [ArticleController::class, 'show'])->name('articles.show');

// --- Konsultasi AI (RF-05..RF-13, RF-25, RF-26) ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/konsultasi', [ConsultationController::class, 'index'])->name('consultation.index');
    Route::post('/konsultasi', [ConsultationController::class, 'ask'])->middleware('throttle:consult')->name('consultation.ask');

    Route::get('/riwayat', [ConsultationController::class, 'history'])->name('history.index');
    Route::get('/riwayat/{session}', [ConsultationController::class, 'show'])->name('history.show');

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

    Route::get('/artikel', [AdminController::class, 'articles'])->name('admin.articles');
    Route::post('/artikel', [AdminController::class, 'storeArticle'])->name('admin.articles.store');
    Route::patch('/artikel/{article}', [AdminController::class, 'updateArticle'])->name('admin.articles.update');
    Route::delete('/artikel/{article}', [AdminController::class, 'destroyArticle'])->name('admin.articles.destroy');
    Route::get('/verifikasi', [AdminController::class, 'showTwoFactor'])->name('admin.2fa');
    Route::post('/verifikasi/kirim', [AdminController::class, 'sendOtp'])->middleware('throttle:6,1')->name('admin.2fa.send');
    Route::post('/verifikasi', [AdminController::class, 'verifyOtp'])->name('admin.2fa.verify');
});

require __DIR__.'/auth.php';
