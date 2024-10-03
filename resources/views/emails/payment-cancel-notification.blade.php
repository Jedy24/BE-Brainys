@extends('emails.layout')

@section('title', 'Pembatalan Transaksi')

@section('content')
    <p class="title mb-4">Pembayaran Dibatalkan</p>
    <p class="text-base mb-4">Tagihan pembayaran Anda dengan nomor <strong>{{ $transaction->transaction_code }}</strong>.
        telah dibatalkan oleh sistem karena melebihi batas waktu pembayaran.</p>
    <p class="text-base mb-2">Detail Transaksi:</p>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Nomor Transaksi</strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;">{{ $transaction->transaction_code }}</td>
        </tr>
        @php
            if ($transaction->detail->item_type === 'PACKAGE') {
                $package = Package::find($transaction->detail->item_id);
                $packageType =
                    $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
                $jenisTransaksi = 'Pembelian ' . $record->transaction_name . ' (' . $packageType . ')';
            } else {
                $jenisTransaksi = 'Pembelian ' . $transaction->transaction_name;
            }
        @endphp
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Jenis Transaksi</strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;">{{ $jenisTransaksi }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Total</strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;">Rp.
                {{ number_format($transaction->amount_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Metode Pembayaran</strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #dddddd;">{{ $transaction->payment->service_name }}</td>
        </tr>
    </table>

    <p class="text-base mb-4">Anda dapat melakukan pembelian ulang jika masih membutuhkan produk atau layanan
        yang sama. Mohon perhatikan batas waktu pembayaran untuk memastikan transaksi Anda berhasil.</p>

    {{-- Button Disini --}}
    <a href="{{ url(
        env('BRAINYS_MODE') === 'STAGING'
            ? 'https://staging.brainys.oasys.id/langganan/daftar-paket'
            : 'https://brainys.oasys.id/langganan/daftar-paket',
    ) }}"
        class="btn-primary">
        Buat Transaksi Baru
    </a>

    <p class="text-base mt-6">Terima kasih telah menggunakan Brainys. Jika ada pertanyaan atau masalah, jangan
        ragu untuk menghubungi tim dukungan kami untuk bantuan lebih lanjut.</p>

    <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
@endsection
