@extends('emails.layout')

@section('title', 'Notifikasi Login')

@section('content')
    <p class="title mb-4">Halo {{ $user->name }}!</p>
    <p class="text-base mb-4">Kami mendeteksi aktivitas login baru ke akun Anda pada:</p>
    <p class="text-base-no-space mb-0"><strong>Tanggal dan Waktu:</strong> {{ $loginTime }}</p>
    <p class="text-base-no-space mb-2"><strong>IP Address:</strong> {{ $ipAddress }}</p>
    <p class="text-base mb-4">Jika ini adalah Anda, silakan abaikan pesan ini dan tidak perlu tindakan lebih lanjut. Namun jika Anda merasa tidak melakukan aktivitas ini. Segera hubungi tim dukungan kami untuk bantuan lebih lanjut.</p>
    <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
@endsection
