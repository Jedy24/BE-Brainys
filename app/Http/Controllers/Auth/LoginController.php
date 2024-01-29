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
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Email atau Password salah!',
                'data' => $validate->errors(),
            ], 403);
        }

        // Check if email exist
        $user = User::where('email', $request->email)->first();

        // Check password & verified user
        if (!$user || !$user->otp_verified_at || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data salah atau Akun belum melakukan verifikasi OTP!',
            ], 401);
        }

        // Check if the profile is completed
        if (!$user->profile_completed) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Lengkapi profile Anda untuk melanjutkan.',
            ], 403);
        }

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Berhasil masuk.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
}
