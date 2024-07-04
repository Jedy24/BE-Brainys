<?php

use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\SyllabusController;
use App\Http\Controllers\Api\HintController;
use App\Http\Controllers\Api\BahanAjarController;
use App\Http\Controllers\Api\UserStatusController;
use App\Http\Controllers\Api\UpdateMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Api\FeedbackReviewController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\OpenAIController;
use App\Http\Controllers\Api\SendInvitationController;
use App\Http\Controllers\Api\UserInvitationController;
use App\Http\Controllers\Service\CapaianPembelajaranController;

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

// API routes for register & login
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

// Public routes of authentication
Route::controller(AuthenticationController::class)->group(function () {
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
    Route::post('/resend-otp', 'resendOtp');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::get('/user-profile', [AuthenticationController::class, 'userProfile']);
    Route::get('/new-user', [AuthenticationController::class, 'newUser']);
    Route::get('/user-status', [UserStatusController::class, 'getStatus']);
    Route::post('/profile', [AuthenticationController::class, 'profile']);
    Route::post('/change-password', [AuthenticationController::class, 'changePassword']);
    Route::post('/update-profile', [AuthenticationController::class, 'updateProfile']);
    Route::post('/user-invitations/redeem', [UserInvitationController::class, 'redeem']);

    // History
    Route::group(['prefix' => 'history'], function () {
        Route::get('/', [HistoryController::class, 'showHistory']);
        Route::post('/', [HistoryController::class, 'showHistoryFilter']);
    });

    // Material
    Route::group(['prefix' => 'material'], function () {
        Route::post('/generate', [MaterialController::class, 'generate']);
        Route::post('/export-word', [MaterialController::class, 'convertToWord']);
        Route::get('/history', [MaterialController::class, 'history']);
        Route::get('/history/{id}', [MaterialController::class, 'historyDetail']);
    });

    // Syllabus
    Route::group(['prefix' => 'syllabus'], function () {
        Route::post('/generate', [SyllabusController::class, 'generate']);
        Route::get('/history', [SyllabusController::class, 'history']);
        Route::get('/history/{id}', [SyllabusController::class, 'historyDetail']);
        Route::post('/export-word', [SyllabusController::class, 'convertToWord']);
    });

    // Exercise
    Route::group(['prefix' => 'exercise'], function () {
        Route::post('/generate-essay', [ExerciseController::class, 'generateEssay']);
        Route::post('/generate-choice', [ExerciseController::class, 'generateChoice']);
        Route::post('/export-word', [ExerciseController::class, 'convertToWord']);
        Route::get('/history', [ExerciseController::class, 'history']);
        Route::get('/history/{id}', [ExerciseController::class, 'historyDetail']);
    });

    // Gamification
    Route::group(['prefix' => 'gamification'], function () {
        Route::post('/generate', [GamificationController::class, 'generate']);
        Route::post('/export-word', [GamificationController::class, 'convertToWord']);
        Route::post('/export-ppt', [GamificationController::class, 'convertToPPT']);
    });

    // Hint
    Route::group(['prefix' => 'hint'], function () {
        Route::post('/generate', [HintController::class, 'generate']);
        Route::post('/export-word', [HintController::class, 'convertToWord']);
        Route::get('/history', [HintController::class, 'history']);
        Route::get('/history/{id}', [HintController::class, 'historyDetail']);
    });

    // Bahan Ajar
    Route::group(['prefix' => 'bahan-ajar'], function () {
        Route::post('/generate', [BahanAjarController::class, 'generate']);
        Route::post('/export-word', [BahanAjarController::class, 'convertToWord']);
        Route::post('/export-ppt', [BahanAjarController::class, 'convertToPPT']);
        Route::get('/history', [BahanAjarController::class, 'history']);
        Route::get('/history/{id}', [BahanAjarController::class, 'historyDetail']);
    });

    // Feedback
    Route::group(['prefix' => 'feedback'], function () {
        Route::get('/', [FeedbackReviewController::class, 'index']);
        Route::post('/create', [FeedbackReviewController::class, 'store']);
    });
});

// Route for google log-in
Route::get('login/{provider}', [SocialiteController::class, 'redirect']);
Route::get('login/{provider}/callback', [SocialiteController::class, 'callback']);
Route::post('login/{provider}/callback', [SocialiteController::class, 'callback']);

// Route for get system update message
Route::get('/check-updates', [UpdateMessageController::class, 'checkUpdates']);
Route::get('/show-updates/{id}', [UpdateMessageController::class, 'showUpdates']);

// Open AI API
Route::group(['prefix' => 'open-ai'], function () {
    Route::get('/credit', [OpenAIController::class, 'checkCredit']);
});

// Capaian Pembelajaran
Route::group(['prefix' => 'capaian-pembelajaran'], function () {
    Route::post('/mata-pelajaran', [CapaianPembelajaranController::class, 'getMataPelajaran']);
    Route::post('/fase', [CapaianPembelajaranController::class, 'getFase']);
    Route::post('/element', [CapaianPembelajaranController::class, 'getElement']);
    Route::post('/subelement', [CapaianPembelajaranController::class, 'getSubElement']);
});

Route::post('/send-invitation', [SendInvitationController::class, 'sendInvitation'])->name('api.send-invitation');
