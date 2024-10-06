@extends('emails.layout')

@section('title', 'New User Notification')

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
    <p class="title">Selamat Datang {{ $user->name }}!</p>
    <p class="text-base">Kami tidak sabar menunggu Anda mencoba semua template yang
        ada di Brainys, mulai dari modul ajar, bahan ajar hingga kisi-kisi
        soal dan alur tujuan pembelajaran yang sudah berbasis kurikulum merdeka.
    </p>

    {{-- Button Disini --}}
    <div style="text-align: center;">
        <a href="{{ url(
            env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id'
                : 'https://brainys.oasys.id',
        ) }}" class="btn-primary">
            Masuk ke Brainys
        </a>
    </div>
@endsection
