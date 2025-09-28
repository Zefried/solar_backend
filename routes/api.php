<?php

use App\Http\Controllers\AuthController\BasicAuth\BasicAuthController;
use App\Http\Controllers\AuthController\BasicAuth\GetUserController;
use App\Http\Controllers\KycController\FetchKycDetailController;
use App\Http\Controllers\KycController\KycController;
use App\Http\Controllers\KycController\KycReportController;
use App\Http\Controllers\KycController\UpdateKycController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Registration Routes
Route::post('/admin/register', [BasicAuthController::class, 'adminRegister']);
Route::post('/employee/register', [BasicAuthController::class, 'employeeRegister']);
Route::post('/user/register', [BasicAuthController::class, 'userRegister']);

// Login Route for all users
Route::post('/login', [BasicAuthController::class, 'login']);

// Get User Route
    // Employee role specific routes - Keep separate for clarity
Route::get('/fetch/employee', [GetUserController::class, 'fetchEmployee']);
Route::get('/search/employee', [GetUserController::class, 'searchEmployee']);

Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // creating resource 
    Route::post('/doc/upload', [KycController::class, 'createOrUpdateDocs']);
    Route::post('/personal-info', [KycController::class, 'createOrUpdatePersonalInfo']);
    Route::post('/bank-info', [KycController::class, 'createOrUpdateBankInfo']);
    Route::post('/extra-info', [KycController::class, 'createOrUpdateExtraInfo']);

    // fetching resource
    Route::get('/docs/fetch', [FetchKycDetailController::class, 'fetchDocs']);
    Route::get('/personal-info/fetch', [FetchKycDetailController::class, 'fetchPersonalInfo']);
    Route::get('/bank-info/fetch', [FetchKycDetailController::class, 'fetchBankInfo']);
    Route::get('/extra-info/fetch', [FetchKycDetailController::class, 'fetchExtraInfo']);
    Route::post('/download', [FetchKycDetailController::class, 'downloadFiles']);

    // Update KYC 
    Route::post('/update/bank-info', [UpdateKycController::class, 'updateBankInfo']);
    Route::post('/update/personal-info', [UpdateKycController::class, 'updatePersonalInfo']);
    Route::post('/update/extra-info', [UpdateKycController::class, 'updateExtraInfo']);
    Route::post('/update/documents', [UpdateKycController::class, 'updateDocuments']);
    
    // kyc reporting
    Route::post('/dashboard/reports', [KycReportController::class, 'test']);
});





 Route::post('/test', [KycController::class, 'updateKycTracking']);





