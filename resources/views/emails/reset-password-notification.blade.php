@extends('emails.layout')

@section('title', 'Reset Password Notification')

@section('styles')
    <style>
        .btn-primary {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3758F9;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            color: white;
        }
    </style>
@endsection

@section('content')
    <p class="title">Permintaan Reset Password</p>
    <p class="text-base">Kami menerima permintaan untuk mengatur ulang kata sandi Brainys Anda.</p>

    <!-- Tombol Reset Password -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="{{ url(
            env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id/forget-password?email=' . $user->email . '&token=' . $user->reset_token
                : 'https://brainys.oasys.id/forget-password?email=' . $user->email . '&token=' . $user->reset_token,
        ) }}"
            class="btn-primary">
            Reset Password
        </a>
    </div>

    <p class="text-base" style="margin-top: 20px;">
        Namun jika Anda merasa tidak melakukan aktivitas ini, segera hubungi tim dukungan kami untuk bantuan lebih lanjut.
    </p>
@endsection
