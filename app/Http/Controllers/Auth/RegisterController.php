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
use Illuminate\Support\Str;
use App\Mail\OtpNotification;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        /**Validasi data user. */
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email:rfc,dns|max:250|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Terjadi kesalahan, periksa Email atau Password!',
                'data' => $validate->errors(),
            ], 403);
        }

        /**Generate kode OTP dengan 6 digit angka. **/
        $otp = rand(100000, 999999);

        /**Membuat akun user dan menampilkan kode OTP. */
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
        ]);

        /**Kode OTP hanya berlaku selama 2 menit untuk setiap kode OTP. */
        $user->otp_expiry = now()->addMinutes(2);
        $user->save();

        //Mail
        Mail::to($user->email)->send(new OtpNotification($user));

        $data['user'] = $user;

        /**Pesan sukses */
        $response = [
            'status' => 'success',
            'message' => 'Akun berhasil dibuat!',
            'data' => $data,
        ];

        /**Mengembalikan nilai dalam bentuk JSON. */
        return response()->json($response, 201);
    }
}
