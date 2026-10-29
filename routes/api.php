<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('/health', [HealthController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('libraries/{library}', [LibraryController::class, 'show']);
    Route::post('libraries', [LibraryController::class, 'store']);
    Route::put('libraries/{library}', [LibraryController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('me', [AuthController::class, 'me']);
    Route::put('me/profile', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'changePassword']);

    Route::post('logout', [AuthController::class, 'logout']);
});
