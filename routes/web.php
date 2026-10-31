<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentClaimController;
use App\Http\Controllers\GeneratedDocumentController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\LedgerEntryController;
use App\Http\Controllers\LedgerFactController;
use App\Http\Controllers\LedgerImportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('ledger', [LedgerEntryController::class, 'index'])->name('ledger.index');
    Route::post('ledger/import', [LedgerImportController::class, 'store'])->name('ledger.import');
    Route::post('ledger', [LedgerEntryController::class, 'store'])->name('ledger.store');
    Route::put('ledger/{ledgerEntry}', [LedgerEntryController::class, 'update'])->name('ledger.update');
    Route::delete('ledger/{ledgerEntry}', [LedgerEntryController::class, 'destroy'])->name('ledger.destroy');

    Route::post('ledger/{ledgerEntry}/facts', [LedgerFactController::class, 'store'])->name('facts.store');
    Route::put('facts/{ledgerFact}', [LedgerFactController::class, 'update'])->name('facts.update');
    Route::delete('facts/{ledgerFact}', [LedgerFactController::class, 'destroy'])->name('facts.destroy');

    Route::get('jobs', [JobPostingController::class, 'index'])->name('jobs.index');
    Route::put('jobs/preferences', [JobSearchController::class, 'update'])->name('jobs.preferences.update');
    Route::post('jobs/search', [JobSearchController::class, 'search'])->name('jobs.search');
    Route::post('jobs/{jobPosting}/save', [JobSearchController::class, 'save'])->name('jobs.save');
    Route::post('jobs/{jobPosting}/dismiss', [JobSearchController::class, 'dismiss'])->name('jobs.dismiss');
    Route::post('jobs', [JobPostingController::class, 'store'])->name('jobs.store');
    Route::get('jobs/{jobPosting}', [JobPostingController::class, 'show'])->name('jobs.show');
    Route::delete('jobs/{jobPosting}', [JobPostingController::class, 'destroy'])->name('jobs.destroy');

    Route::post('jobs/{jobPosting}/documents', [GeneratedDocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{generatedDocument}', [GeneratedDocumentController::class, 'show'])->name('documents.show');
    Route::post('documents/{generatedDocument}/retry', [GeneratedDocumentController::class, 'retry'])->name('documents.retry');
    Route::post('documents/{generatedDocument}/approve', [GeneratedDocumentController::class, 'approve'])->name('documents.approve');
    Route::get('documents/{generatedDocument}/export', [GeneratedDocumentController::class, 'export'])->name('documents.export');
    Route::get('documents/{generatedDocument}/pdf', [GeneratedDocumentController::class, 'exportPdf'])->name('documents.export-pdf');
    Route::delete('documents/{generatedDocument}', [GeneratedDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::put('claims/{documentClaim}', [DocumentClaimController::class, 'update'])->name('claims.update');
    Route::delete('claims/{documentClaim}', [DocumentClaimController::class, 'destroy'])->name('claims.destroy');
});

require __DIR__.'/settings.php';
