    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Menunggu Pembayaran</title>
        <style>
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

        .otp-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 24px;
            text-align: center;
        }

        .bold-text {
            font-weight: bold;
        }

        .text-base {
            font-size: 16px;
            color: #000000;
            text-align: center; /* Pusatkan teks */
        }

        .content {
            padding: 30px;
            text-align: left;
        }

        .content p, .content ol {
            text-align: left; /* Teks di kiri untuk bagian konten */
        }

        .footer {
            background-color: #f0f4f8;
            color: #888888;
            padding: 20px;
            text-align: center;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .btn-primary {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .bold-text {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #dddddd;
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
                <table>
                    <tr>
                        <td>Nomor Transaksi</td>
                        <td><span class="bold-text">{{ $transaction->transaction_code }}</span></td>
                    </tr>
                    <tr>
                        <td>Jenis Transaksi</td>
                        <td><span class="bold-text">{{ $transaction->transaction_name }}</span></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td><span class="bold-text">Rp {{ number_format($transaction->amount_total, 0, ',', '.') }}</span></td>
                    </tr>
                    <tr>
                        <td>Metode Pembayaran</td>
                        <td><span class="bold-text">{{ $transactionPayment->service_name }}</span></td>
                    </tr>
                </table>

                @if($paymentMethod->category === 'others')
                    <p class="text-base">Ikuti instruksi pembayaran QRIS di bawah ini:</p>
                    <img src="{{ $transactionPayment->qrcode_url }}" alt="QR Code" style="max-width: 200px; height: auto;">
                @else
                    <p class="text-base">Ikuti instruksi pembayaran VA di bawah ini</strong></p>
                    <p class="text-base">Virtual Account: <span class="bold-text"> {{ $transactionPayment->virtual_account }} </span></p>
                @endif

                <p class="text-lg mb-4">Cara melakukan pembayaran {{ $paymentMethod->name }} </p>
                @if($paymentMethod->id === 1)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 2)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 3)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 4)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 5)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 6)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @elseif($paymentMethod->id === 7)
                    <p class="text-base mb-4">{!! $paymentMethod->description !!}</p>
                @else
                    <p class="text-base mb-4">Cara pembayaran tidak tersedia.</p>
                @endif

                <p class="text-base">Atau akses pembayaran dengan klik tombol dibawah</p>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{  }}" style="display: inline-block; background-color: #007bff; color: #ffffff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Bayar Sekarang</a>
                    <a href="{{  }}" style="display: inline-block; margin-left: 10px; background-color: #f0f4f8; color: #007bff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Bantuan</a>
                </div>

            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
            </div>
        </div>
    </body>

    </html>
