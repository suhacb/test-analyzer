<?php

use App\Http\Controllers\AcceptanceCriteriaController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TestExecutionController;
use App\Http\Controllers\TestScenarioController;
use App\Http\Controllers\UserStoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

Route::resource('user-stories', UserStoryController::class)->only(['index', 'show']);
Route::resource('acceptance-criteria', AcceptanceCriteriaController::class)->only(['index', 'show']);
Route::get('test-scenarios/export', [TestScenarioController::class, 'export'])->name('test-scenarios.export');
Route::resource('test-scenarios', TestScenarioController::class)->only(['index', 'show']);
Route::resource('test-executions', TestExecutionController::class)->only(['index', 'show']);

Route::prefix('review')->name('review.')->group(function () {
    Route::get('/',             [ReviewController::class, 'index'])->name('index');
    Route::get('pending',       [ReviewController::class, 'pending'])->name('pending');
    Route::get('flagged',       [ReviewController::class, 'flagged'])->name('flagged');
    Route::get('failed-jobs',   [ReviewController::class, 'failedJobs'])->name('failed-jobs.index');

    Route::post('executions/bulk-dismiss',              [ReviewController::class, 'bulkDismissFlags'])->name('executions.bulk-dismiss');

    Route::patch('executions/{testExecution}',          [ReviewController::class, 'updateExecution'])->name('executions.update');
    Route::post('executions/{testExecution}/analyse',   [ReviewController::class, 'analyseExecution'])->name('executions.analyse');
    Route::post('executions/{testExecution}/dismiss-flag', [ReviewController::class, 'dismissFlag'])->name('executions.dismiss-flag');

    Route::post('failed-jobs/{uuid}/retry',  [ReviewController::class, 'retryJob'])->name('failed-jobs.retry');
    Route::delete('failed-jobs/{uuid}',      [ReviewController::class, 'dismissJob'])->name('failed-jobs.dismiss');
});
