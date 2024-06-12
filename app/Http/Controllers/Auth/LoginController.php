<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        /**Validasi data user. */
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validate->errors(),
            ], 403);
        }

        /**Cek apakah email user ada atau tidak */
        $user = User::where('email', $request->email)->first();

        /**Cek password user dan status verifikasi OTP */
        if (!$user || !$user->otp_verified_at || !Hash::check($request->password, $user->password)) {
            $errorMessage = '';

            /**Pesan error jika email user tidak ada. */
            if (!$user) {
                $errorMessage = 'Email tidak ditemukan, periksa lagi Email Anda.';
            } else if (!Hash::check($request->password, $user->password)) {
                $errorMessage = 'Password salah, periksa lagi password Anda.';
            } else if (!$user->otp_verified_at) {
                $errorMessage = 'Akun belum melakukan verifikasi OTP, silakan melakukan verifikasi OTP.';
            }

            /**Menampilkan pesan error. */
            return response()->json([
                'status' => 'failed',
                'message' => $errorMessage,
            ], 401);
        }

        /**Mengecek status kelengkapan profile. */
        if (!$user->profile_completed) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Lengkapi profile Anda sebelum melakukan log-in.',
            ], 403);
        }

        /**Generate token untuk digunakan pada fungsi setelah log-in atau protected API route. */
        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        /**Response pesan sukses. */
        $response = [
            'status' => 'success',
            'message' => 'Berhasil masuk.',
            'data' => $data,
        ];

        /**Mengembalikan nilai dalam bentuk JSON. */
        return response()->json($response, 200);
    }
}
