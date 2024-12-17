<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserMovieController;

Route::get('/', [UserMovieController::class, 'index'])->name('dashboard');

Route::middleware(['auth:web', 'role:user'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('film/show/{id}', [UserMovieController::class, 'show'])->name('user.film.show');
    Route::get('film/booking/{scheduleId}', [BookingController::class, 'index'])->name('user.booking.index');
    Route::get('/booking/konfirmasi/{scheduleId}', [BookingController::class, 'konfirmasi'])->name('user.booking.konfirmasi');
    Route::get('/booking/show', [BookingController::class, 'show'])->name('user.booking.list');
    Route::post('film/booking/store', [BookingController::class, 'store'])->name('user.booking.store');
    Route::get('/booking/edit/{id}', [BookingController::class, 'edit'])->name('user.booking.edit');
    Route::put('/booking/update/{id}', [BookingController::class, 'update'])->name('user.booking.update');
    Route::delete('/booking/delete/{id}', [BookingController::class, 'destroy'])->name('user.booking.destroy');
});

Route::get('admin/dashboard', function () {
    return view('admin.dashboard');
});

Route::get('film', [UserMovieController::class, 'index'])->name('user.film.index');

Route::get('storage/{path}', function($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    return response()->file($fullPath, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Headers' => '*',
    ]);
})->where('path', '.*');

Route::get('storage/posters/{filename}', function($filename) {
    $path = storage_path('app/public/posters/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Headers' => '*',
        'Content-Type' => 'image/jpeg',
    ]);
})->where('filename', '.*');

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
