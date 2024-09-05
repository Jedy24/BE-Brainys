<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembatalan Transaksi</title>
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
            <p class="text-lg mb-4">Halo <strong>{{ $user->name }}</strong>,</p>
            <p class="text-base mb-4">Kami ingin menginformasikan bahwa transaksi Anda dengan nomor
                <strong>{{ $transaction->transaction_code }}</strong> telah dibatalkan karena melebihi batas waktu
                pembayaran.</p>
            <p class="text-base mb-2">Detail Transaksi:</p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Nomor Transaksi:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dddddd;">{{ $transaction->transaction_code }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dddddd;"><strong>Total Bayar:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dddddd;">Rp.
                        {{ number_format($transaction->amount_total, 0, ',', '.') }}</td>
                </tr>
            </table>

            <p class="text-base mb-4">Anda dapat melakukan pembelian ulang jika masih membutuhkan produk atau layanan
                yang sama. Mohon perhatikan batas waktu pembayaran untuk memastikan transaksi Anda berhasil.</p>

            <p class="text-base mt-6">Terima kasih telah menggunakan Brainys. Jika ada pertanyaan atau masalah, jangan
                ragu untuk menghubungi tim dukungan kami.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
        </div>
    </div>
</body>

</html>
