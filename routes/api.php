<?php

use App\Http\Controllers\AuthController\BasicAuth\BasicAuthController;
use App\Http\Controllers\AuthController\BasicAuth\GetUserController;
use App\Http\Controllers\KycController\FetchKycDetailController;
use App\Http\Controllers\KycController\KycController;
use App\Http\Controllers\KycController\KycReportController;
use App\Http\Controllers\KycController\UpdateKycController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Registration Routes
Route::post('/admin/register', [BasicAuthController::class, 'adminRegister']);
Route::post('/employee/register', [BasicAuthController::class, 'employeeRegister']);
Route::post('/user/register', [BasicAuthController::class, 'userRegister']);
    Route::get('/search/employee/register', [GetUserController::class, 'searchEmployeeInRegister']); // associate an employee 

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
    Route::post('/dashboard/reports', [KycReportController::class, 'userDashboardReport']);
});


// Admin and Employee specific routes
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/get/user/list', [GetUserController::class, 'getUsersList']);
    
    // Fetching details of users associated with the employee
    Route::get('/clients/bank-info/{id}', [GetUserController::class, 'getUserBankInfo']);
    Route::get('/clients/doc-info/{id}', [GetUserController::class, 'getUserDocInfo']);
    Route::get('/clients/personal-info/{id}', [GetUserController::class, 'getUserPersonalInfo']);
    Route::get('/clients/extra-info/{id}', [GetUserController::class, 'getUserExtraInfo']);

    Route::prefix('employee')->group(function () {
        Route::get('/search/users', [GetUserController::class, 'searchUsers']);
       
    });

    Route::prefix('admin')->group(function () {
        Route::get('/fetch/users', [GetUserController::class, 'fetchUsers']);
        Route::get('/view/employee', [GetUserController::class, 'viewEmployee']);
        Route::post('/update/employee/{id}', [GetUserController::class, 'updateEmployee']);
        Route::post('/add/employee/', [GetUserController::class, 'addEmployeeByAdmin']);
        Route::get('/employee/list', [GetUserController::class, 'getEmployeesList']);
        Route::get('/employee/users/{id}', [GetUserController::class, 'employeeUsersList']);
    });

});








