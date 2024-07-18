<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\SendInvitationController;
use App\Http\Controllers\Controller;
use App\Models\AutoInviteEmail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInvitation;
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
        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception) {
            return response()->json(['error' => 'Invalid credentials provided.']);
        }

        /**Membuat user baru dengan email yang tersedia.
         * Secara default passwordnya adalah 123456789.
         */
        $userCreated = User::firstOrCreate(
            [
                'email' => $user->getEmail(),
            ],
            [
                'email_verified_at' => now(),
                'name' => $user->getName(),
                'status' => true,
            ]
        );

        /**Relasi model providers dengan model Users.
         * Bertujuan untuk membuat record baru pada tabel Providers.
         */
        $userCreated->providers()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $user->getId(),
            ],
            [
                'avatar' => $user->getAvatar(),
            ]
        );

        /**Generate token. */
        $token = $userCreated->createToken('token-name')->plainTextToken;

        // Cek apakah user is_active bernilai 0 atau false
        if (!$userCreated->is_active) {
            // Ekstrak domain email
            $emailDomain = substr(strrchr($user->getEmail(), "@"), 1);

            // Cek apakah email_domain ada dan is_active bernilai 1
            $autoInviteEmail = AutoInviteEmail::where('email_domain', $emailDomain)
                ->where('is_active', 1)
                ->first();

            if ($autoInviteEmail) {
                // Periksa apakah sudah ada undangan untuk email ini
                $existingInvitation = UserInvitation::where('email', $user->getEmail())->first();

                if ($existingInvitation) {
                    // Jika sudah ada undangan, kirim ulang undangan
                    self::sendInvitation($existingInvitation);
                } else {
                    // Membuat invite code
                    $inviteCode = $this->generateRandomCode(8);
                    $invitation = UserInvitation::create([
                        'email' => $user->getEmail(),
                        'invite_code' => $inviteCode,
                        'is_used' => false,
                        'expired_at' => now()->addDays(30),
                    ]);

                    // Mengirim email undangan
                    self::sendInvitation($invitation);
                }
            }
        }

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
    protected function validateProvider($provider)
    {
        if (!in_array($provider, ['facebook', 'github', 'google'])) {
            return response()->json(['error' => 'Silakan masuk menggunakan akun Facebook, GitHub, atau Google'], 422);
        }
    }

    // Send Invite
    public static function sendInvitation(UserInvitation $user)
    {
        try {
            // Create a new request instance
            $request = new \Illuminate\Http\Request();
            $request->replace(['email' => $user->email]);

            // Create an instance of the SendInvitationController
            $controller = new SendInvitationController();

            // Call the sendInvitation method
            $response = $controller->sendInvitation($request);

            // Handle response from the controller
            $responseData = $response->getData();
        } catch (\Exception $e) {
        }
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @param int $length
     * @return string
     */
    protected static function generateRandomCode($length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
