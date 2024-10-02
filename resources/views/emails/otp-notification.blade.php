@extends('emails.layout')

@section('title', 'OTP Notification')

@section('content')
    <p class="title">Verifikasi Kode OTP</p>
    <p class="text-base">Halo! Berikut Kode OTP untuk verifikasi akun Anda: <span class="bold-text">{{ $user->otp }}</span>
    </p>
    <p class="text-base">
        Jika Anda merasa tidak melakukan aktivitas ini, abaikan pesan ini.<br>
        Atau segera hubungi tim dukungan kami untuk bantuan lebih lanjut.
    </p>

    <!-- Link bantuan dengan styling khusus -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
