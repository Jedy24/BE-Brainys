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
                <p class="text-lg mb-4">Halo {{ $user->name }},</p>
                <p class="text-base mb-4">Menunggu Pembayaran dengan <strong>{{ $transactionPayment->service_name }}</strong> sebelum <strong>
                    {{ $transactionPayment->expired }}</strong></p>
                <p class="text-base mb-2">Segera lakukan pembayaran dengan detail sebagai berikut:</p>
                <p class="text-base mb-2"><strong>Nomor Transaksi:</strong> {{ $transaction->transaction_code }}</p>
                <p class="text-base mb-2"><strong>Total Bayar: </strong> Rp. {{ number_format($transaction->amount_total, 0, ',', '.') }} </p>
                <p class="text-base mb-2"><strong>Metode Pembayaran:</strong> {{ $transactionPayment->service_name }}</p>
                @if($paymentMethod->category === 'others')
                    <p class="text-base mb-2"><strong>Scan QR Code untuk membayar:</strong></p>
                    <img src="{{ $transactionPayment->qrcode_url }}" alt="QR Code" style="max-width: 200px; height: auto;">
                @else
                    <p class="text-base mb-2"><strong>Virtual Account:</strong> {{ $transactionPayment->virtual_account }}</p>
                @endif

                <p class="text-lg mb-4"><strong>Cara Pembayaran:</strong></p>
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

                <p class="text-base mb-4"><strong>Mohon lakukan pembayaran dalam jangka waktu 1x24 jam. Jika tidak, pembelian Anda akan dibatalkan.</strong></p>
                <p class="text-base mt-6">Kami selalu siap membantu Anda jika ada pertanyaan atau masalah. Terima kasih
                    telah menggunakan Brainys!</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
            </div>
        </div>
    </body>

    </html>
