<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// JWT Authentication Routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});

// Movies Routes
Route::resource('/movies', MovieController::class);

// Booking Routes
Route::prefix('booking')->group(function () {
    Route::get('/list', [BookingController::class, 'list']);
    Route::get('/{scheduleId}', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::get('/konfirmasi/{scheduleId}', [BookingController::class, 'konfirmasi']);
    Route::get('/detail/{id}', [BookingController::class, 'show']);
    Route::put('/{id}', [BookingController::class, 'update']);
    Route::delete('/{id}', [BookingController::class, 'destroy']);
});

// Add this new route group after booking routes
Route::prefix('storage')->group(function () {
    Route::get('/posters/{filename}', function ($filename) {
        $path = storage_path('app/public/posters/' . $filename);
        if (!file_exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        return response()->file($path);
    });
});
