<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Pembayaran</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f8fa;
            color: #333333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #f0f4f8;
            padding: 20px;
            text-align: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .header img {
            max-width: 150px;
            height: auto;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #000000;
        }

        .bold-text {
            font-weight: bold;
        }

        .text-base {
            font-size: 16px;
            color: #000000;
        }

        .content {
            padding: 30px;
            text-align: left;
        }

        .footer {
            background-color: #f0f4f8;
            color: #888888;
            padding: 20px;
            text-align: center;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .help-link {
            display: block;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
            font-weight: bold;
        }
        .help-link:hover {
            color: #0056b3;
        }

        .instructions {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }

        .instructions-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            text-align: center;
            color: #637381;
        }

        .instructions-list p {
            font-size: 16px;
            line-height: 1.6;
            color: #637381;
        }
        .instructions-list ul {
            font-size: 16px;
            line-height: 1.6;
            color: #637381;
        }


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
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="https://be.brainys.oasys.id/img/Logo@2x.png" alt="Brainys Logo">
        </div>

        <!-- Content -->
        <div class="content">
            <p class="title">Menunggu Pembayaran</p>
            <p class="text-base">Tagihan pembayaran Anda telah terbit dengan metode <span class="bold-text">{{ $paymentMethod->name }}</span></p>
            <p class="text-base">Segera lakukan pembayaran sebelum <span class="bold-text">{{ $transactionPayment->expired }}</span>
                dengan rincian pembayaran sebagai berikut</p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Nomor Transaksi</td>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">{{ $transaction->transaction_code }}</span></td>
                </tr>
                <tr>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Jenis Transaksi</td>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">{{ $transaction->transaction_name }}</span></td>
                </tr>
                <tr>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Total</td>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">Rp. {{ number_format($transaction->amount_total, 0, ',', '.') }}</span></td>
                </tr>
                <tr>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;">Metode Pembayaran</td>
                    <td class="text-base" style="padding: 10px; border-bottom: 1px solid #dddddd;"><span class="bold-text">{{ $transactionPayment->service_name }}</span></td>
                </tr>
            </table>

            @if($paymentMethod->category === 'others')
                <p class="text-base">Ikuti instruksi pembayaran QRIS di bawah ini</strong></p>
                <div style="text-align: center;">
                    <img src="{{ $transactionPayment->qrcode_url }}" alt="QR Code" style="max-width: 200px; height: auto;">
                </div>
            @else
                <p class="text-base">Ikuti instruksi pembayaran VA di bawah ini:</p>
                <p class="text-base">Virtual Account: <span class="bold-text"> {{ $transactionPayment->virtual_account }} </span></p>
            @endif

            <div class="instruction">
                <p class="instructions-title">Cara melakukan pembayararan {{ $paymentMethod->name }} :</p>
                @if($paymentMethod->id === 1)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 2)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 3)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 4)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 5)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 6)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 7)
                    <p class="instructions-list">{!! $paymentMethod->description !!}</p>
                @else
                    <p class="instructions-list">Cara pembayaran tidak tersedia.</p>
                @endif
            </div>

            <p class="text-base" style="text-align: center;">Atau akses pembayaran dengan klik tombol di bawah:</p>
            <div style="text-align: center; margin-top: 10px;">
                <a href="https://staging.brainys.oasys.id/order/detail/{{ $transaction->transaction_code }}" class="btn-primary">Bayar Sekarang</a>
                <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
        </div>
    </div>
</body>

</html>
