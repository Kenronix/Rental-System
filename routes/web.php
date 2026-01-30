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
Route::delete('/api/tenants/{id}', [App\Http\Controllers\TenantController::class, 'destroy']);
Route::post('/api/tenants/{id}/generate-account', [App\Http\Controllers\TenantController::class, 'generateAccount']);

// API Routes for payments (landlord only)
Route::get('/api/payments', [App\Http\Controllers\PaymentController::class, 'index']);
Route::post('/api/payments', [App\Http\Controllers\PaymentController::class, 'store']);
Route::put('/api/payments/{id}', [App\Http\Controllers\PaymentController::class, 'update']);
Route::delete('/api/payments/{id}', [App\Http\Controllers\PaymentController::class, 'destroy']);
Route::put('/api/payments/{id}/approve', [App\Http\Controllers\PaymentController::class, 'approve']);
Route::put('/api/payments/{id}/reject', [App\Http\Controllers\PaymentController::class, 'reject']);
Route::get('/api/payments/{id}/receipt', [App\Http\Controllers\ReceiptController::class, 'landlordGenerate']);

// API Routes for reports (landlord only)
Route::get('/api/reports', [App\Http\Controllers\ReportController::class, 'index']);
Route::get('/api/reports/download', [App\Http\Controllers\ReportController::class, 'download']);
Route::get('/api/reports/download-pdf', [App\Http\Controllers\ReportController::class, 'downloadPdf']);
Route::get('/api/reports/download-properties-csv', [App\Http\Controllers\ReportController::class, 'downloadPropertiesCsv']);
Route::get('/api/reports/download-properties-pdf', [App\Http\Controllers\ReportController::class, 'downloadPropertiesPdf']);
Route::get('/api/reports/download-units-csv', [App\Http\Controllers\ReportController::class, 'downloadUnitsCsv']);
Route::get('/api/reports/download-units-pdf', [App\Http\Controllers\ReportController::class, 'downloadUnitsPdf']);
Route::get('/api/reports/download-tenants-csv', [App\Http\Controllers\ReportController::class, 'downloadTenantsCsv']);
Route::get('/api/reports/download-tenants-pdf', [App\Http\Controllers\ReportController::class, 'downloadTenantsPdf']);
Route::get('/api/reports/download-tenant-profile-pdf/{id}', [App\Http\Controllers\ReportController::class, 'downloadTenantProfilePdf']);

// API Routes for tenant (tenant only)
Route::get('/api/tenant/rental', [App\Http\Controllers\TenantRentalController::class, 'getRental']);
Route::get('/api/tenant/utilities', [App\Http\Controllers\TenantUtilitiesController::class, 'index']);
Route::post('/api/tenant/utilities/{id}/pay', [App\Http\Controllers\TenantUtilitiesController::class, 'pay']);
Route::get('/api/tenant/payments', [App\Http\Controllers\TenantPaymentsController::class, 'index']);
Route::get('/api/tenant/notifications', [App\Http\Controllers\TenantNotificationsController::class, 'index']);
Route::put('/api/tenant/notifications/{id}/read', [App\Http\Controllers\TenantNotificationsController::class, 'markAsRead']);
Route::put('/api/tenant/notifications/read-all', [App\Http\Controllers\TenantNotificationsController::class, 'markAllAsRead']);
Route::get('/api/tenant/receipt/{id}', [App\Http\Controllers\ReceiptController::class, 'generate']);

// Admin API Routes
Route::post('/api/admin/login', [AdminController::class, 'login']);
Route::post('/api/admin/logout', [AdminController::class, 'logout']);
Route::get('/api/admin/user', [AdminController::class, 'user']);
Route::get('/api/admin/properties', [App\Http\Controllers\PropertyController::class, 'getAllProperties']);

// Catch-all route for Vue.js SPA
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
