<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Notification</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #e7edf3; /* Warna latar belakang biru muda */
            color: #333333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto; /* Memberikan jarak di atas dan bawah */
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
            max-width: 120px; /* Menyesuaikan ukuran logo */
            height: auto;
        }

        .content {
            padding: 30px;
        }

        .otp-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 24px;
        }

        .otp-code {
            font-weight: bold;
        }

        .text-base {
            font-size: 16px;
            color: #000000; /* Warna teks yang lebih lembut */
        }

        .footer {
            background-color: #f0f4f8;
            color: #888888;
            padding: 20px;
            text-align: center;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Styling untuk link bantuan */
        .help-link {
            display: block;
            text-align: center;
            color: #007bff; /* Warna biru */
            text-decoration: none; /* Menghilangkan underline */
            font-size: 16px;
            margin-top: 50px;
            font-weight: bold;
        }

        .help-link:hover {
            color: #0056b3; /* Warna saat di-hover */
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
            <!-- Mengubah ukuran dan bold pada judul OTP -->
            <p class="otp-title">Verifikasi Kode OTP</p>
            <p class="text-base">Halo! Berikut Kode OTP untuk verifikasi akun Anda: <span class="otp-code">{{ $user->otp }}</span></p>
            <p class="text-base">Jika Anda merasa tidak melakukan aktivitas ini, abaikan pesan ini.<br>
                Atau segera hubungi tim dukungan kami untuk bantuan lebih lanjut.</p>

            <!-- Link bantuan dengan styling khusus -->
            <a href="https://api.whatsapp.com/send?phone=6288242021092" class="help-link">Bantuan</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
        </div>
    </div>
</body>

</html>
