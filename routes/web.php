<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// API Routes for authentication
Route::post('/api/login', [LoginController::class, 'login']);
Route::post('/api/logout', [LoginController::class, 'logout']);
Route::get('/api/user', [LoginController::class, 'user']);

// API Routes for properties
Route::post('/api/properties', [App\Http\Controllers\PropertyController::class, 'store']);
Route::get('/api/properties', [App\Http\Controllers\PropertyController::class, 'index']);
Route::get('/api/properties/{id}', [App\Http\Controllers\PropertyController::class, 'show']);
Route::put('/api/properties/{id}', [App\Http\Controllers\PropertyController::class, 'update']);

// API Routes for units
Route::post('/api/units', [App\Http\Controllers\UnitController::class, 'store']);
Route::get('/api/properties/{propertyId}/units', [App\Http\Controllers\UnitController::class, 'index']);

// Catch-all route for Vue.js SPA
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
