<?php

use App\Http\Controllers\BannedPokemonController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomPokemonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetController;
use App\Http\Controllers\PokemonInfoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * Group routes: /api/pet
 */
Route::prefix('pet')->group(function () {
    Route::get('/', [PetController::class, 'index'])->name('index');
    Route::post('/create', [PetController::class, 'createPet']);
    Route::get('{id}', [PetController::class, 'getPet']);
    Route::put('{id}', [PetController::class, 'updatePet']);
    Route::delete('{id}', [PetController::class, 'deletePet']);
});

Route::middleware('auth.secret')->group(function () {
    // Banned
    Route::get('/banned', [BannedPokemonController::class, 'index']);
    Route::post('/banned', [BannedPokemonController::class, 'store']);
    Route::delete('/banned/{name}', [BannedPokemonController::class, 'destroy']);
    // Custom
    Route::get('/custom', [CustomPokemonController::class, 'index']);
    Route::post('/custom', [CustomPokemonController::class, 'store']);
    Route::get('/custom/{id}', [CustomPokemonController::class, 'show']);
    Route::put('/custom/{id}', [CustomPokemonController::class, 'update']);
    Route::delete('/custom/{id}', [CustomPokemonController::class, 'destroy']);
});

// Public info route (no secret key required)
Route::post('/info', [PokemonInfoController::class, 'info']);

// ===== Media Expert =====

Route::get('/slots', [BookingController::class, 'slots']);

Route::post('/bookings', [BookingController::class, 'store']);

Route::delete('/bookings/{booking}', [BookingController::class, 'cancel']);
