<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class SocialiteController extends Controller
{
    public function redirect($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        return Socialite::driver($provider)->stateless()->redirect();
    }

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
}
