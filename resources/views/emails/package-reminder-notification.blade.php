@extends('emails.layout')

@section('title', 'Tagihan Perpanjangan ' . $package->name . ' Telah Terbit')

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
    <p class="title">Paket anda Segera Berakhir {{ $userPackage->days_remaining }} Hari Lagi.</p>

    @php
        $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
    @endphp

    <p class="text-base">Tagihan {{ $package->name }} ({{ $packageType }}) Anda telah terbit, segera lakukan pembayaran
        sebelum <b>{{ $userPackage->expired_at->format('d M Y') }}</b>.
        Untuk terus menggunakan Brainys dengan manfaat lebih banyak.</p>

    <div style="text-align: center;">
        <a href="{{ url(
            env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id/langganan/daftar-paket'
                : 'https://brainys.oasys.id/langganan/daftar-paket',
        ) }}"
            class="btn-primary">
            Perpanjang Paket
        </a>
    </div>

    <p class="text-base">
        Jika Anda merasa sudah melakukan pembayaran, silakan abaikan pesan ini. Atau hubungi tim dukungan kami untuk bantuan
        lebih lanjut.
    </p>

    <!-- Link bantuan dengan styling khusus -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
