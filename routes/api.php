<?php

use App\Http\Controllers\Api\AcceptanceCriteriaController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TestExecutionController;
use App\Http\Controllers\Api\TestScenarioController;
use App\Http\Controllers\Api\UserStoryController;
use Illuminate\Support\Facades\Route;

Route::get('stats', StatsController::class)->name('api.stats');

Route::apiResource('user-stories', UserStoryController::class)->names('api.user-stories');
Route::apiResource('acceptance-criteria', AcceptanceCriteriaController::class)->names('api.acceptance-criteria');
Route::apiResource('test-scenarios', TestScenarioController::class)->names('api.test-scenarios');
Route::apiResource('test-executions', TestExecutionController::class)->names('api.test-executions');
