@extends('emails.layout')

@section('title', 'Anda saat ini menggunakan paket gratis')

@section('styles')
    <!-- Additional styles specific to this template -->
    <style>
        .btn-primary {
            display: inline-block;
            padding: 10px 50px;
            background-color: #3758F9;
            color: white !important;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background-color: #3758F9;
            color: white;
        }
    </style>
@endsection

@section('content')
    <p class="title">Halo {{ $user->name }}</p>
    <p class="text-base">Anda saat ini berlangganan paket gratis di Brainys!</p>
    <p class="text-base">
        Manfaat yang anda dapatkan pada <b>Paket Gratis</b>:<br>
        <ul>
            <li>5 credit per bulan</li>
            <li>Akses semua templat</li>
            <li>Akses download file word, excel dan powerpoint</li>
        </ul>
    </p>
    <p class="text-base">
        Paket gratis ini akan diperpanjang secara otomatis pada {{ $userPackage->expired_at->format('d M Y') }}. Untuk terus menggunakan Brainys dengan
        manfaat lebih besar, aktifkan kembali langganan paket berbayar di bawah ini
    </p>

    <div style="text-align: center;">
        <a href="{{ url(
            env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id/langganan/daftar-paket'
                : 'https://brainys.oasys.id/langganan/daftar-paket',
        ) }}"
            class="btn-primary">
            Upgrade Paket
        </a>
    </div>

    <!-- Link bantuan dengan styling khusus -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
