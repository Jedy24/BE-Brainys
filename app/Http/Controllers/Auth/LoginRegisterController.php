<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Notifications\OtpNotification;
use App\Notifications\ResetPasswordNotification;

class LoginRegisterController extends Controller
{
     /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'email' => 'required|string|email:rfc,dns|max:250|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Terjadi kesalahan, email atau password salah!',
                'data' => $validate->errors(),
            ], 403);
        }

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
        ]);

        $user->notify(new OtpNotification($otp));

        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Akun berhasil dibuat!',
            'data' => $data,
        ];

        return response()->json($response, 201);
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Berhasil masuk.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    /**
     * Log out the user from application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil keluar.'
            ], 200);
    }

    /**
     * Get the profile of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
            ],
        ];

        return response()->json($response, 200);
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function callback($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        try{
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception){
            return response()->json(['error' => 'Invalid credentials provided.']);
        }

        $userCreated = User::firstOrCreate([
            'email' => $user->getEmail(),
        ],
        [
            'email_verified_at' => now(),
            'name' => $user->getName(),
            'status' => true,
            'password' => Hash::make('123456789'),
        ]);

        $userCreated->providers()->updateOrCreate([
            'provider' => $provider,
            'provider_id' => $user->getId(),
        ],
        [
            'avatar' => $user->getAvatar(),
        ]);

        $token = $userCreated->createToken('token-name')->plainTextToken;

        $response = [
            'id' => $userCreated->id,
            'name' => $userCreated->name,
            'email' => $userCreated->email,
            'token' => $token,
        ];

        return response()->json($response, 200);
    }

    protected function validateProvider($provider){
        if (!in_array($provider, ['facebook', 'github', 'google'])) {
            return response()->json(['error' => 'Silakan masuk menggunakan akun Facebook, GitHub, atau Google'], 422);
        }
    }

    /**
     * Change the password for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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


    /**
     * Forgot password notification function.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
                'message' => 'Pengguna email tidak ditemukan.',
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

    /**
     * Reset password function
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Verify OTP for the registered user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
                'message' => 'Email atau OTP salah',
            ], 401);
        }

        // Clear OTP after successful verification
        $user->otp = null;
        $user->otp_verified_at = now();
        $user->save();

        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'Verifikasi berhasil!',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
}
