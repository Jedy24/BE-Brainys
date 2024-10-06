@extends('emails.layout')

@section('title', 'Pembelian Ekstra Credit Berhasil!')

@section('content')
    <p class="title">Halo {{ $user->name }}</p>

    @php
        use App\Models\ExtraCredit;
        use App\Models\UserPackage;

        $credit = ExtraCredit::find($transaction->details->item_id);
        $userPackage = UserPackage::where('id_user', $user->id)->first();
    @endphp

    <p class="text-base">Terima kasih telah melakukan pembelian {{ $credit->credit_amount }} Ekstra Credit di Brainys!</p>

    <p class="text-base">
        Credit ini akan aktif hingga periode berlangganan pada {{ $userPackage->expired_at->format('d M Y') }}. Jika ada
        pertanyaan, jangan ragu hubungi tim
        dukungan kami untuk bantuan lebih lanjut.
    </p>

    <!-- Link bantuan dengan styling khusus -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
