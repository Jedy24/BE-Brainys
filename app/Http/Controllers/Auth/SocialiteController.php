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
        /**Validasi provider, jika tidak null maka akan mengembalikan proses redirect.
         * Jika null maka proses redirect tidak akan dilanjutkan.
         */
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        /**Mengarahkan user ke halaman autentikasi. */
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback($provider)
    {
        /**Melakukan validasi, jika validasi gagal maka fungsi callback akan mengembalikan pesan error. */
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        /**Mengambil data user dari library Socialite.
         * Jika gagal maka akan mengembalikan pesan error.
         */
        try{
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception){
            return response()->json(['error' => 'Invalid credentials provided.']);
        }

        /**Membuat user baru dengan email yang tersedia.
         * Secara default passwordnya adalah 123456789.
         */
        $userCreated = User::firstOrCreate([
            'email' => $user->getEmail(),
        ],
        [
            'email_verified_at' => now(),
            'name' => $user->getName(),
            'status' => true,
            'password' => Hash::make('123456789'),
        ]);

        /**Relasi model providers dengan model Users.
         * Bertujuan untuk membuat record baru pada tabel Providers.
         */
        $userCreated->providers()->updateOrCreate([
            'provider' => $provider,
            'provider_id' => $user->getId(),
        ],
        [
            'avatar' => $user->getAvatar(),
        ]);

        /**Generate token. */
        $token = $userCreated->createToken('token-name')->plainTextToken;

        /**Response JSON akan mengembalikan data user berupa ID, nama, email, dan token. */
        $response = [
            'id' => $userCreated->id,
            'name' => $userCreated->name,
            'email' => $userCreated->email,
            'token' => $token,
        ];

        /**Mengembalikan nilai dalam bentuk JSON. */
        return response()->json($response, 200);
    }

    /**Validasi penyedia provider, untuk sementara hanya untuk Google secara project realnya.
     * Jika mau dikembangkan maka bisa diubah-ubah sesuai kebutuhan.
     * Jika penyedia provider tidak valid maka akan ditampilkan pesan error.
     */
    protected function validateProvider($provider){
        if (!in_array($provider, ['facebook', 'github', 'google'])) {
            return response()->json(['error' => 'Silakan masuk menggunakan akun Facebook, GitHub, atau Google'], 422);
        }
    }
}
