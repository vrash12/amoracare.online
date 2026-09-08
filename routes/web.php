<?php

use App\Http\Controllers\Admin\AdoptionCaseController;
use App\Http\Controllers\Admin\AdoptionMatchingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ChildController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\ParentApplicationQrController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Parent\ParentAiGuidanceController;
use App\Http\Controllers\Parent\ParentApplicationController;
use App\Http\Controllers\Parent\ParentDashboardController;
use App\Http\Controllers\Parent\ParentDocumentController;
use App\Http\Controllers\Public\ProspectiveParentApplicationController;
use App\Http\Controllers\Reviewer\AuthorizedCaseController;
use App\Http\Controllers\Reviewer\ReviewerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/terms-and-conditions', 'legal.terms')
    ->name('legal.terms');

Route::view('/privacy-notice', 'legal.privacy')
    ->name('legal.privacy');

Route::get('/apply/adoptive-parent', [ProspectiveParentApplicationController::class, 'create'])
    ->name('parent.application.create');

Route::post('/apply/adoptive-parent', [ProspectiveParentApplicationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('parent.application.store');

Route::get('/apply/adoptive-parent/submitted', [ProspectiveParentApplicationController::class, 'submitted'])
    ->name('parent.application.submitted');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Custom authentication. Breeze is not used.
|--------------------------------------------------------------------------
*/

// The public sign-in and OTP pages only check the legacy web guard. Portal
// guards remain independent so another role can sign in on the same browser.
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/email/verify', [EmailVerificationController::class, 'show'])
        ->name('email.verification.notice');

    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('email.verification.verify');

    Route::post('/email/verification-code', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('email.verification.resend');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth:admin,prospective_parent,external_reviewer,web')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard Redirect
|--------------------------------------------------------------------------
| Redirects users based on their role:
| admin               -> /admin/dashboard
| prospective_parent  -> /parent/dashboard
| external_reviewer   -> /reviewer/dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:admin,web', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/matching', [AdoptionMatchingController::class, 'index'])
            ->name('matching.index');

        Route::post('/matching/run', [AdoptionMatchingController::class, 'run'])
            ->name('matching.run');

        Route::get('/matching/{matching}', [AdoptionMatchingController::class, 'show'])
            ->name('matching.show');

        Route::resource('users', UserController::class);
        Route::get('/parents/application-qr', [ParentApplicationQrController::class, 'show'])
            ->name('parents.application-qr');
        Route::get('/parents/application-qr/image', [ParentApplicationQrController::class, 'image'])
            ->name('parents.application-qr.image');
        Route::get('/parents/application-qr/download', [ParentApplicationQrController::class, 'download'])
            ->name('parents.application-qr.download');
        Route::resource('parents', ParentController::class);
        Route::resource('children', ChildController::class);
        Route::post('/matching/results/{result}/create-case', [AdoptionMatchingController::class, 'createCase'])
            ->name('matching.create-case');
        Route::resource('adoption-cases', AdoptionCaseController::class);
        Route::post('/adoption-cases/{adoptionCase}/notes', [AdoptionCaseController::class, 'storeNote'])
            ->name('adoption-cases.notes.store');

        Route::put('/adoption-cases/{adoptionCase}/documents/{document}', [AdoptionCaseController::class, 'updateDocument'])
            ->name('adoption-cases.documents.update');
        Route::resource('donations', DonationController::class);

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::prefix('reports')
            ->name('reports.')
            ->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('index');

                Route::get('/children', [ReportController::class, 'children'])->name('children');
                Route::get('/adoption-cases', [ReportController::class, 'adoptionCases'])->name('adoption-cases');
                Route::get('/donations', [ReportController::class, 'donations'])->name('donations');

                Route::get('/children/export', [ReportController::class, 'exportChildren'])->name('children.export');
                Route::get('/adoption-cases/export', [ReportController::class, 'exportAdoptionCases'])->name('adoption-cases.export');
                Route::get('/donations/export', [ReportController::class, 'exportDonations'])->name('donations.export');
            });
    });

/*
|--------------------------------------------------------------------------
| Prospective Adoptive Parent Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:prospective_parent,web', 'role:prospective_parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('/dashboard', [ParentDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/application', [ParentApplicationController::class, 'index'])
            ->name('application.index');

        Route::get('/documents', [ParentDocumentController::class, 'index'])
            ->name('documents.index');

        Route::post('/documents/{document}/upload', [ParentDocumentController::class, 'upload'])
            ->name('documents.upload');

        Route::get('/documents/{document}/download', [ParentDocumentController::class, 'download'])
            ->name('documents.download');

        Route::get('/ai-legal-guidance', [ParentAiGuidanceController::class, 'index'])
            ->name('ai.index');

        Route::post('/ai-legal-guidance/chat', [ParentAiGuidanceController::class, 'chat'])
            ->name('ai.chat');

        Route::post('/ai-legal-guidance/clear', [ParentAiGuidanceController::class, 'clear'])
            ->name('ai.clear');
    });

/*
|--------------------------------------------------------------------------
| DSWD/RACCO External Reviewer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:external_reviewer,web', 'role:external_reviewer'])
    ->prefix('reviewer')
    ->name('reviewer.')
    ->group(function () {
        Route::get('/dashboard', [ReviewerDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/authorized-cases', [AuthorizedCaseController::class, 'index'])
            ->name('cases.index');

        Route::get('/authorized-cases/{access}', [AuthorizedCaseController::class, 'show'])
            ->name('cases.show');

        Route::post('/authorized-cases/{access}/notes', [AuthorizedCaseController::class, 'storeNote'])
            ->name('cases.notes.store');

        Route::post('/authorized-cases/{access}/approve', [AuthorizedCaseController::class, 'approve'])
            ->name('cases.approve');

        Route::post('/authorized-cases/{access}/request-changes', [AuthorizedCaseController::class, 'requestChanges'])
            ->name('cases.request-changes');

        Route::get('/authorized-cases/{access}/documents/{document}/download', [AuthorizedCaseController::class, 'downloadDocument'])
            ->name('cases.documents.download');

        Route::post('/authorized-cases/{access}/documents/{document}/accept', [AuthorizedCaseController::class, 'acceptDocument'])
            ->name('cases.documents.accept');

        Route::post('/authorized-cases/{access}/documents/{document}/reject', [AuthorizedCaseController::class, 'rejectDocument'])
            ->name('cases.documents.reject');
    });

Route::fallback(function () {
    return redirect()->route('home');
});
