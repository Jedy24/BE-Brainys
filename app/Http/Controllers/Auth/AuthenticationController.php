<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\SendInvitationController;
use App\Http\Controllers\Controller;
use App\Models\AutoInviteEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
// use App\Notifications\OtpNotification;
use App\Mail\OtpNotification;
use App\Mail\ResetPasswordNotification;
use App\Mail\NewUserNotification;
// use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Mail;

class AuthenticationController extends Controller
{
    // Handle get profile user
    public function userProfile(Request $request)
    {
        $user = $request->user();

        /**Message error jika token dari log-in salah. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Akun tidak terotentifikasi.',
            ], 401);
        }

        // Get the user's packages with the package names
        $userPackages = $user->userPackages()->with('package')->get()->map(function ($userPackage) {
            return [
                'user_package_id' => $userPackage->id,
                'package_id' => $userPackage->package->id,
                'package_name' =>  $userPackage->package->name . '' .
                    ($userPackage->package->type === 'monthly'
                        ? ' (Bulanan)'
                        : ($userPackage->package->type === 'annually'
                            ? ' (Tahunan)'
                            : '')),
                'package_description' => $userPackage->package->description,
                'package_description_mod' => 'Mendapatkan ' . $userPackage->package->credit_add_monthly . ' credit setiap bulannya',
                'credit_add_monthly' => $userPackage->package->credit_add_monthly,
                'price' => $userPackage->package->price,
                'enroll_at' => $userPackage->enroll_at,
                'expired_at' => $userPackage->expired_at,
                'is_renewable' => $userPackage->is_renewable,
                'enroll_at_formatted' => $userPackage->enroll_at->format('d M Y'),
                'expired_at_formatted' => $userPackage->expired_at->format('d M Y'),
            ];
        });

        /**Mengambil data user berupa nama, email, nama sekolah, dan profesi. */
        $response = [
            'status' => 'success',
            'message' => 'Data akun berhasil diambil!',
            'data' => [
                'name' => $user->name,
                'school_level' => $user->school_level,
                'school_name' => $user->school_name,
                'profession' => $user->profession,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'package' => $userPackages
                // 'password' => $user->password,
            ],
        ];

        /**Mengembalikan nilai dalam bentuk JSON */
        return response()->json($response, 200);
    }

    // Handle log-out function
    public function logout(Request $request)
    {
        /**Menghapus token otentifikasi.
         * Tanpa token tersebut maka user tidak dapat mengakses sumber daya.
         * Mengembalikan nilai dalam bentuk JSON.
         */
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil keluar.'
        ], 200);
    }

    // Handle change password function
    public function changePassword(Request $request)
    {
        $user = $request->user();

        /**Validasi data user. */
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        /**Cek kesamaan current password dengan password pada DB.
         * Jika tidak sesuai maka muncul pesan error.
         */
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Current password tidak sesuai.',
            ], 401);
        }

        /**Jika validasi berhasil maka membuat password yang baru.
         * Kemudian menyimpan data user.
         */
        $user->password = Hash::make($request->new_password);
        $user->save();

        /**Mengembalikan nilai dalam bentuk JSON.
         * Menampilkan pesan sukses.
         */
        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah!',
        ], 200);
    }

    // Handle forgot password function
    public function forgotPassword(Request $request)
    {
        /**Validasi data user. */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        /**Mencari email user. */
        $user = User::where('email', $request->email)->first();

        /**Jika email tidak ada maka muncul pesan error. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email tidak ditemukan, periksa lagi Email Anda.',
            ], 404);
        }

        /**Jika email ditemukan maka akan generate token dengan 60 karakter.
         * Token tersebut digunakan untuk token reset password.
         * Token reset memiliki masa aktif untuk satu jam.
         */
        $token = Str::random(60);
        $user->reset_token = $token;
        $user->reset_token_expired = now()->addHours(1);
        $user->save();

        /**Mengirim pesan reset password ke email. */
        Mail::to($user->email)->send(new ResetPasswordNotification($user));

        /**Mengembalikan nilai dalam bentuk JSON.
         * Menampilkan pesan sukses.
         */
        return response()->json([
            'status' => 'success',
            'message' => 'Reset password telah dikirim ke email.',
            'reset_token' => $token,
        ], 200);
    }

    // Handle reset password function
    public function resetPassword(Request $request)
    {
        /**Validasi data user. */
        $validator = Validator::make($request->all(), [
            'reset_token' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        /** Mencari data user berdasarkan reset token dan masa berlakunya */
        $user = User::where('reset_token', $request->reset_token)
            ->where('reset_token_expired', '>', now())
            ->first();

        /**Jika data tidak sesuai maka muncul pesan error. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reset token salah atau sudah kedaluwarsa.',
            ], 401);
        }

        /**Jika data sesuai maka akan membuat password baru.
         * Token reset dan masa berlakunya akan dihapus.
         * Menyimpan data user.
         */
        $user->password = Hash::make($request->new_password);
        $user->reset_token = null;
        $user->reset_token_expired = null;
        $user->save();

        /**Mengembalikan nilai dalam bentuk JSON.
         * Menampilkan pesan sukses.
         */
        return response()->json([
            'status' => 'success',
            'message' => 'Reset password berhasil.',
        ], 200);
    }

    // Handle verify OTP function
    public function verifyOtp(Request $request)
    {
        /**Validasi data user. */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|min:6|max:6',
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal',
                'data' => $validator->errors(),
            ], 422);
        }

        /**Cek email dan kode OTP user.
         * Cek apakah kode OTP masih berlaku atau tidak.
         */
        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('otp_expiry', '>=', now())
            ->first();

        /**Jika user tidak ditemukan, kode OTP salah atau kode OTP tidak valid, munculkan pesan error */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email atau OTP salah, silakan periksa kembali.',
            ], 401);
        }

        /**Jika verifikasi OTP berhasil, maka OTP dan OTP expiry akan dihapus.
         * Setelah verifikasi OTP maka akan ada waktu verifikasinya sebagai bukti telah melakukan verifikasi OTP.
         * Kelengkapan profile secara default bernilai false agar user diharuskan melengkapi profile sebelum melakukan log-in.
         * Menyimpan data user.
         */
        $user->otp = null;
        $user->otp_expiry = null;
        $user->otp_verified_at = now();
        $user->profile_completed = false;
        $user->save();

        /**Generate token untuk melengkapi profile */
        $token = $user->createToken('otp-token')->plainTextToken;

        $data['token'] = $token;
        $data['user'] = $user;

        // Cek apakah user is_active bernilai 0 atau false
        if (!$user->is_active) {
            // Ekstrak domain email
            $emailDomain = substr(strrchr($user->email, "@"), 1);

            // Cek apakah email_domain ada dan is_active bernilai 1
            $autoInviteEmail = AutoInviteEmail::where('email_domain', $emailDomain)
                ->where('is_active', 1)
                ->first();

            if ($autoInviteEmail) {
                // Periksa apakah sudah ada undangan untuk email ini
                $existingInvitation = UserInvitation::where('email', $user->email)->first();

                if ($existingInvitation) {
                    // Jika sudah ada undangan, kirim ulang undangan
                    self::sendInvitation($existingInvitation);
                } else {
                    // Membuat invite code
                    $inviteCode = $this->generateRandomCode(8);
                    $invitation = UserInvitation::create([
                        'email' => $user->email,
                        'invite_code' => $inviteCode,
                        'is_used' => false,
                        'expired_at' => now()->addDays(30),
                    ]);

                    // Mengirim email undangan
                    self::sendInvitation($invitation);
                }
            }
        }

        /**Memunculkan pesan sukses setelah selesai verifikasi. */
        $response = [
            'status' => 'success',
            'message' => 'Verifikasi berhasil! Silakan lengkapi profile untuk proses selanjutnya.',
            'data' => $data,
        ];

        /**Mengembalikan nilai dalam bentuk JSON. */
        return response()->json($response, 200);
    }

    public function resendOtp(Request $request)
    {
        /** Validasi data user. */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        /** Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal',
                'data' => $validator->errors(),
            ], 422);
        }

        /** Mencari user berdasarkan email. */
        $user = User::where('email', $request->email)->first();

        /** Jika user tidak ditemukan, menampilkan pesan error. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        /** Jika user sudah terverifikasi maka muncul pesan bahwa sudah terverifikasi. */
        if ($user->otp_verified_at) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User sudah terverifikasi.',
            ], 422);
        }

        /** Generate kode OTP baru. */
        $otp = rand(100000, 999999);

        /** Simpan kode OTP baru dan atur waktu berlaku. */
        $user->otp = $otp;
        $user->otp_expiry = now()->addMinutes(2);
        $user->save();

        /** Mengirim ulang kode OTP ke email user. */
        Mail::to($user->email)->send(new OtpNotification($user));

        /** Respon sukses. */
        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP berhasil dikirim ulang.',
            'data' => [
                'otp' => $otp,
            ],
        ], 200);
    }

    // Handle profile registration
    public function profile(Request $request)
    {
        /**Validasi data user. */
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'school_level' => 'required|string|max:255',
            'school_name' => 'required|string|max:255',
            'profession' => 'required|string|max:255',
        ]);

        /**Jika validasi gagal maka muncul pesan error. */
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        /**Mencari user. */
        $user = $request->user();

        /**Memunculkan pesan error jika user tidak ditemukan. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        /**Jika ditemukan maka akan memperbaharui data user. */
        $user->update([
            'name' => $request->name,
            'school_level' => $request->school_level,
            'school_name' => $request->school_name,
            'profession' => $request->profession,
        ]);

        /**Kelengkapan profile akan menjadi true sehingga user dapat melakukan log-in.
         * Menyimpan data user.
         */
        $user->profile_completed = true;
        $user->save();

        Mail::to($user->email)->send(new NewUserNotification($user));

        $data['user'] = $user;

        /**Memunculkan pesan sukses setelah selesai melengkapi profile. */
        $response = [
            'status' => 'success',
            'message' => 'Profile pengguna berhasil dilengkapi!',
            'data' => $data,
        ];

        /**Mengembalikan nilai dalam bentuk JSON. */
        return response()->json($response, 200);
    }

    // Handle update profile user
    public function updateProfile(Request $request)
    {
        /* Verifikasi token dari log-in **/
        $user = $request->user();

        /* Pesan error jika token dari login tidak valid **/
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Akun tidak terotentifikasi.',
            ], 401);
        }

        /* Validasi data yang diterima dari request **/
        $request->validate([
            'name' => 'required|string|max:255',
            'school_level' => 'required|string|max:255',
            'school_name' => 'required|string|max:255',
            'profession' => 'required|string|max:255',
        ]);

        /* Mengupdate data profil pengguna **/
        $user->update([
            'name' => $request->input('name'),
            'school_level' => $request->input('school_level'),
            'school_name' => $request->input('school_name'),
            'profession' => $request->input('profession'),
        ]);

        /* Mengembalikan respons sukses **/
        $response = [
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui!',
            'data' => [
                'name' => $user->name,
                'school_level' => $user->school_level,
                'school_name' => $user->school_name,
                'profession' => $user->profession,
            ],
        ];

        return response()->json($response, 200);
    }

    //Get message for new user
    public function newUser(Request $request)
    {
        $user = $request->user();

        /**Message error jika token dari log-in salah. */
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Akun tidak terotentifikasi.',
            ], 401);
        }

        $welcomeMessage = "Hello " . $user->name . "! Welcome to Brainys.";

        /**Menyimpan data ke dalam database */
        UserNotification::create([
            'user_id' => $user->id,
            'message' => $welcomeMessage,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan selamat datang berhasil disimpan.',
            'welcome_message' => $welcomeMessage,
        ], 200);
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
