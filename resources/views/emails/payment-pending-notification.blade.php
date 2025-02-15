@extends('emails.layout')

@section('title', 'Menunggu Pembayaran')

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
            margin-top: 20px;
        }

        .btn-primary:hover {
            background-color: #3758F9;
            color: white;
        }

        .va-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: left;
            font-size: 16px;
        }

        .va-number {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .va-number .va-text {
            margin-right: auto;
        }

        .instructions {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 16px;
            text-align: left;
        }

        .instructions-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0px;
            text-align: center;
            color: #637381;
        }

        .instructions-sub-title {
            font-size: 16px;
            margin-top: 0px;
            margin-bottom: 10px;
            text-align: center;
            color: #637381;
        }

        .instructions-list {
            font-size: 16px;
            line-height: 1.6;
            color: #3d3d3d;
        }
    </style>
@endsection

@section('content')
    <p class="title">Menunggu Pembayaran</p>
    <p class="text-base">Tagihan pembayaran Anda telah terbit</p>
    <p class="text-base">Segera lakukan pembayaran sebelum <span class="bold-text">{{ $transactionPayment->expired ? $transactionPayment->expired->format('d-m-Y H:i') : '-' }}</span> dengan rincian pembayaran sebagai berikut:</p>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Nomor Transaksi</td>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">{{ $transaction->transaction_code }}</span></td>
        </tr>
        @php
            use App\Models\Package;

            $transactionDetail = $transaction->details->first();
            if ($transactionDetail && $transactionDetail->item_type === 'PACKAGE') {
                $package = Package::find($transactionDetail->item_id);
                $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
                $jenisTransaksi = 'Pembelian ' . $transaction->transaction_name . ' (' . $packageType . ')';
            } else {
                $jenisTransaksi = 'Pembelian ' . $transaction->transaction_name;
            }
        @endphp
        <tr>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Jenis Transaksi</td>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">{{ $jenisTransaksi }}</span></td>
        </tr>
        <tr>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Total</td>
            <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">Rp {{ number_format($transaction->amount_total, 0, ',', '.') }}</span></td>
        </tr>
    </table>

    <p class="text-base" style="text-align: center;">Atau akses pembayaran dengan klik tombol di bawah:</p>
    <div style="text-align: center; margin-top: 10px;">
        <a href="{{ url(
            env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id/order/detail/' . $transaction->transaction_code
                : 'https://brainys.oasys.id/order/detail/' . $transaction->transaction_code
        ) }}"
           class="btn-primary">
           Bayar Sekarang
        </a>
        <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
    </div>
@endsection
