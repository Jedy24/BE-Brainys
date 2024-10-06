@extends('emails.layout')

@section('title', 'Paket Anda Telah Aktif')

@section('content')
    <p class="title">Halo {{ $user->name }}</p>

    @php
        use App\Models\Package;
        use App\Models\UserPackage;

        if ($transaction->details->item_type === 'PACKAGE') {
            $package = Package::with('details')->find($transaction->details->item_id);
            $userPackage = UserPackage::where('id_user', $user->id)->first();

            $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
        }
    @endphp

    <p class="text-base">Terima kasih anda telah berlangganan [{{ $package->name }} ({{ $packageType }})] di Brainys!
    </p>

    <p class="text-base">
        Manfaat yang anda dapatkan pada <b>{{ $package->name }}</b>:<br>
    <ul>
        @foreach ($package->details as $detail)
            <li>{{ $detail->name }}</li>
        @endforeach
    </ul>
    </p>

    <p class="text-base">
        Paket dasar ini akan diperpanjang secara otomatis pada {{ $userPackage->expired_at->format('d M Y') }}, setelah menyelesaikan
        pembayaran di periode
        berikutnya.
    </p>

    <!-- Link bantuan dengan styling khusus -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
