<?php

use App\Http\Controllers\Api\v1\AnimalController;
use App\Http\Controllers\Api\v1\BreedingController;
use App\Http\Controllers\Api\v1\ClimateController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\FeedController;
use App\Http\Controllers\Api\v1\FinanceController;
use App\Http\Controllers\Api\v1\HealthController;
use App\Http\Controllers\Api\v1\MilkController;
use App\Http\Controllers\Api\v1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Dashboard & Metrics
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Animals & Herd Management
    Route::get('/animals', [AnimalController::class, 'index']);
    Route::get('/animals/{id}', [AnimalController::class, 'show']);
    Route::post('/animals', [AnimalController::class, 'store']);
    Route::put('/animals/{id}', [AnimalController::class, 'update']);

    // Milk Recording & Dairy Operations
    Route::get('/milk', [MilkController::class, 'index']);
    Route::post('/milk/record', [MilkController::class, 'storeRecord']);
    Route::get('/milk/summary', [MilkController::class, 'summary']);

    // Health, Veterinary & Antimicrobial Withdrawal Control
    Route::get('/health', [HealthController::class, 'index']);
    Route::get('/health/medicines', [HealthController::class, 'medicines']);
    Route::post('/health/cases', [HealthController::class, 'storeCase']);
    Route::post('/health/treatments', [HealthController::class, 'storeTreatment']);

    // Breeding, Insemination & Calving
    Route::get('/breeding', [BreedingController::class, 'index']);
    Route::post('/breeding/events', [BreedingController::class, 'storeEvent']);

    // Feeds, Inventory & Consumption
    Route::get('/feeds', [FeedController::class, 'index']);
    Route::post('/feeds/consumption', [FeedController::class, 'storeConsumption']);

    // Finance, Revenue & Cost-per-Liter
    Route::get('/finances', [FinanceController::class, 'index']);
    Route::post('/finances/transactions', [FinanceController::class, 'storeTransaction']);

    // Climate & NRC THI Heat Stress Index
    Route::get('/climate/current', [ClimateController::class, 'current']);
    Route::post('/climate/readings', [ClimateController::class, 'store']);

    // Workforce & Daily Farm Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
});
