<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\LoginRegisterController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes of authentication (login & register)
Route::controller(LoginRegisterController::class)->group(function() {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('verify-otp', 'verifyOtp');
});

// Protected routes
Route::middleware('auth:sanctum')->group( function () {
    Route::post('/logout', [LoginRegisterController::class, 'logout']);
    Route::get('/user-profile', [LoginRegisterController::class, 'userProfile']);
    Route::post('/change-password', [LoginRegisterController::class, 'changePassword']);
});

// Route for google log-in
Route::get('login/{providser}', [LoginRegisterController::class, 'redirect']);
Route::get('login/{provider}/callback', [LoginRegisterController::class, 'callback']);
Route::post('login/{provider}/callback', [LoginRegisterController::class, 'callback']);
