<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminController;
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
Route::get('/api/properties/{propertyId}/units/{unitId}', [App\Http\Controllers\UnitController::class, 'show']);
Route::put('/api/properties/{propertyId}/units/{unitId}', [App\Http\Controllers\UnitController::class, 'update']);

// Public API Routes for tenant applications
Route::get('/api/units/{unitId}', [App\Http\Controllers\TenantApplicationController::class, 'getUnit']);
Route::post('/api/tenant-applications', [App\Http\Controllers\TenantApplicationController::class, 'store']);

// Protected API Routes for tenant applications (landlord only)
Route::get('/api/tenant-applications/{id}', [App\Http\Controllers\TenantApplicationController::class, 'show']);
Route::put('/api/tenant-applications/{id}/approve', [App\Http\Controllers\TenantApplicationController::class, 'approve']);
Route::put('/api/tenant-applications/{id}/reject', [App\Http\Controllers\TenantApplicationController::class, 'reject']);

// API Routes for tenants (landlord only)
Route::get('/api/tenants', [App\Http\Controllers\TenantController::class, 'index']);
Route::get('/api/tenants/{id}', [App\Http\Controllers\TenantController::class, 'show']);

// Admin API Routes
Route::post('/api/admin/login', [AdminController::class, 'login']);
Route::post('/api/admin/logout', [AdminController::class, 'logout']);
Route::get('/api/admin/user', [AdminController::class, 'user']);
Route::get('/api/admin/properties', [App\Http\Controllers\PropertyController::class, 'getAllProperties']);

// Catch-all route for Vue.js SPA
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
