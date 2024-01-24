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
                'message' => 'An error occurred, please check your email or password!',
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

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User is created successfully.',
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
                'message' => 'Email or Password is incorrect!',
                'data' => $validate->errors(),
            ], 403);
        }

        // Check if email exist
        $user = User::where('email', $request->email)->first();

        // Check password with data in database
        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid credential'
                ], 401);
        }

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User is logged in successfully.',
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
            'message' => 'User is logged out successfully'
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
                'message' => 'User not authenticated.',
            ], 401);
        }

        $response = [
            'status' => 'success',
            'message' => 'User profile retrieved successfully.',
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
            return response()->json(['error' => 'Please login using facebook, github, or google'], 422);
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
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Current password is incorrect',
            ], 401);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
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
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        // Check if email and OTP match
        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid OTP or email',
            ], 401);
        }

        // Clear OTP after successful verification
        $user->otp = null;
        $user->save();

        $data['token'] = $user->createToken($request->email)->plainTextToken;
        $data['user'] = $user;

        $response = [
            'status' => 'success',
            'message' => 'User verified successfully. OTP is correct.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
}
