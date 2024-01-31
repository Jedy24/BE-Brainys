<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Notifications\OtpNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    // Handle get profile user
    public function userProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Akun tidak terotentifikasi.',
            ], 401);
        }

        $response = [
            'status' => 'success',
            'message' => 'Data akun berhasil diambil!',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'school_name' => $user->school_name,
                'profession' => $user->profession,
            ],
        ];

        return response()->json($response, 200);
    }

    // Handle log-out function
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil keluar.'
            ], 200);
    }

    // Handle change password function
    public function changePassword(Request $request)
    {
        $user = $request->user();

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Current password tidak sesuai.',
            ], 401);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah!',
        ], 200);
    }

    // Handle forgot password function
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email tidak ditemukan, periksa lagi Email Anda.',
            ], 404);
        }

        $token = Str::random(60);
        $user->reset_token = $token;
        $user->reset_token_expired = now()->addHours(1);
        $user->save();

        // Send reset password email notification
        $user->notify(new ResetPasswordNotification($token));

        return response()->json([
            'status' => 'success',
            'message' => 'Reset password telah dikirim ke email.',
            'reset_token' => $token,
        ], 200);
    }

    // Handle reset password function
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'new_password' => 'required|string|min:8|confirmed',
            'reset_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->where('reset_token', $request->reset_token)
            ->where('reset_token_expired', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reset token salah atau sudah kedaluwarsa.',
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->reset_token = null;
        $user->reset_token_expired = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reset password berhasil.',
        ], 200);
    }

    // Handle verify OTP function
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal',
                'data' => $validator->errors(),
            ], 422);
        }

        // Check if email and OTP match
        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email atau OTP salah, silakan periksa kembali.',
            ], 401);
        }

        // Clear OTP after successful verification
        $user->otp = null;
        $user->otp_verified_at = now();
        $user->profile_completed = false;
        $user->save();

        $token = $user->createToken('otp-token')->plainTextToken;

        $data['token'] = $token;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Verifikasi berhasil! Silakan lengkapi profile untuk proses selanjutnya.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    // Handle profile registration
    public function profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'school_name' => 'required|string|max:255',
            'profession' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        // Update user profile information
        $user->update([
            'name' => $request->name,
            'school_name' => $request->school_name,
            'profession' => $request->profession,
        ]);

        $user->profile_completed = true;
        $user->save();

        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Profile pengguna berhasil dilengkapi!',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
}
