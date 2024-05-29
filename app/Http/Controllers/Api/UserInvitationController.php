<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserInvitationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Redeem an invitation.
     */
    public function redeem(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'invite_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validasi gagal. Silakan cek kembali input Anda.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Get the current authenticated user
        $user = $request->user();

        // Check if the user is already active
        if ($user->is_active == 1) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Akun Anda sudah aktif. Anda tidak dapat klaim undangan lagi.',
            ], 403);
        }

        // Find the invitation by invite_code
        $invitation = UserInvitation::where('invite_code', $request->invite_code)->first();

        if (!$invitation) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Kode undangan tidak valid.',
            ], 404);
        }

        // Check if the invitation is already used
        if ($invitation->is_used == 1) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Undangan ini sudah digunakan.',
            ], 403);
        }

        // Check if the invitation email matches the current user's email
        if ($user->email !== $invitation->email) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email tidak sesuai dengan email undangan.',
            ], 403);
        }

        // Check if the invitation has expired
        if ($invitation->expired_at < now()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Undangan telah kadaluarsa.',
            ], 403);
        }

        // Update user's is_active status
        $user->is_active = 1;
        $user->save();

        // Update the invitation's is_used status
        $invitation->is_used = 1;
        $invitation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Undangan berhasil digunakan.',
        ], 200);
    }
}
