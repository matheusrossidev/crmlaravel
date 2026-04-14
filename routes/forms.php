<?php

declare(strict_types=1);

use App\Http\Controllers\FormPublicController;
use App\Http\Controllers\Tenant\Forms\FormBuilderController;
use App\Http\Controllers\Tenant\Forms\FormController;
use App\Http\Controllers\Tenant\Forms\FormMappingController;
use App\Http\Controllers\Tenant\Forms\FormSubmissionController;
use Illuminate\Support\Facades\Route;

// ── Public (no auth, web middleware for sessions/CSRF) ───────────────────
Route::middleware('web')->group(function () {
    Route::get('/f/{slug}', [FormPublicController::class, 'show'])->name('forms.public.show');
    Route::post('/f/{slug}', [FormPublicController::class, 'submit'])->name('forms.public.submit')
        ->middleware('throttle:30,1');
});

// ── SDK / public API (no auth, no CSRF — pure JSON/JS, CORS enabled) ─────
Route::prefix('api/form/{slug}')->group(function () {
    Route::options('/{any?}', [FormPublicController::class, 'cors'])->where('any', '.*')->name('forms.sdk.cors');
    Route::get('/config.json', [FormPublicController::class, 'config'])->name('forms.sdk.config');
    Route::post('/submit', [FormPublicController::class, 'submit'])->name('forms.sdk.submit')
        ->middleware('throttle:30,1');
    Route::post('/track-view', [FormPublicController::class, 'trackView'])->name('forms.sdk.track')
        ->middleware('throttle:120,1');
});
Route::get('/api/form/{slug}.js', [FormPublicController::class, 'script'])->name('forms.sdk.script');

// ── Tenant routes (auth + tenant + locale) ───────────────────────────────
Route::middleware(['web', 'auth', 'tenant', 'locale'])->group(function () {

    Route::middleware('role:admin,manager')->group(function () {
        // CRUD
        Route::get('/formularios', [FormController::class, 'index'])->name('forms.index');
        Route::get('/formularios/criar', [FormController::class, 'create'])->name('forms.create');
        Route::post('/formularios', [FormController::class, 'store'])->name('forms.store');
        Route::get('/formularios/{form}/editar', [FormController::class, 'edit'])->name('forms.edit');
        Route::put('/formularios/{form}', [FormController::class, 'update'])->name('forms.update');
        Route::delete('/formularios/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
        Route::patch('/formularios/{form}/toggle', [FormController::class, 'toggle'])->name('forms.toggle');
        Route::post('/formularios/{form}/upload-logo', [FormController::class, 'uploadLogo'])->name('forms.upload-logo');
        Route::post('/formularios/{form}/upload-background', [FormController::class, 'uploadBackground'])->name('forms.upload-background');

        // Builder
        Route::get('/formularios/{form}/builder', [FormBuilderController::class, 'edit'])->name('forms.builder');
        Route::put('/formularios/{form}/builder', [FormBuilderController::class, 'save'])->name('forms.builder.save');

        // Mapping
        Route::get('/formularios/{form}/mapeamento', [FormMappingController::class, 'edit'])->name('forms.mapping');
        Route::put('/formularios/{form}/mapeamento', [FormMappingController::class, 'save'])->name('forms.mapping.save');

        // Submissions
        Route::get('/formularios/{form}/submissoes', [FormSubmissionController::class, 'index'])->name('forms.submissions');
        Route::get('/formularios/{form}/submissoes/export', [FormSubmissionController::class, 'export'])->name('forms.submissions.export');
    });
});
