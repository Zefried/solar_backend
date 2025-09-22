<?php

use App\Http\Controllers\AuthController\BasicAuth\BasicAuthController;
use App\Http\Controllers\AuthController\BasicAuth\GetUserController;
use App\Http\Controllers\KycController\KycController;
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
    Route::post('/doc/upload', [KycController::class, 'createOrUpdateDocs']);
    Route::post('/personal-info', [KycController::class, 'createOrUpdatePersonalInfo']);
});





 Route::post('/test', [KycController::class, 'updateKycTracking']);





