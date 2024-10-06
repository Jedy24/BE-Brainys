@extends('emails.layout')

@section('title', 'Invitation Notification')

@section('styles')
    <style>
        .text-invite {
            font-size: 16px;
            color: #000000;
            text-align: center;
        }

        .invitation-code {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000000;
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <p class="title">Anda Menerima Kode Undangan</p>
    <p class="text-base">Halo {{ $invitation->email }}! Terima kasih sudah melakukan registrasi di Brainys.</p>
    <p class="text-invite">Berikut Kode Undangan untuk mengaktifkan akun Anda:</p>
    <p class="invitation-code">{{ $invitation->invite_code }}</p>
    <p class="text-base">Terima kasih dan selamat menggunakan Brainys!</p>
    <p class="text-base">Regards, <br>Admin Brainys</br></p>

    <p class="text-base mt-6">Jika ada pertanyaan atau masalah, jangan
        ragu untuk menghubungi tim dukungan kami untuk bantuan lebih lanjut.</p>
    <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
@endsection
