<?php

use App\Http\Controllers\Api\v1\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [SystemHealthController::class, 'swaggerUi']);
Route::get('/api/documentation', [SystemHealthController::class, 'swaggerUi']);

Route::get('/', function () {
    return view('app');
});

Route::get('/app/{any?}', function () {
    return view('app');
})->where('any', '.*');
