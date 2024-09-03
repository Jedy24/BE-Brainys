<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Login</title>
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
            <p class="text-base mb-4">Kami mendeteksi aktivitas login baru ke akun Anda pada:</p>
            <p class="text-base mb-2"><strong>Tanggal dan Waktu:</strong> {{ $loginTime }}</p>
            <p class="text-base mb-2"><strong>IP Address:</strong> {{ $ipAddress }}</p>
            <p class="text-base mb-4">Jika ini adalah Anda, tidak perlu ada tindakan lebih lanjut. Namun, jika Anda
                merasa tidak melakukan login ini, segera hubungi tim dukungan kami untuk bantuan lebih lanjut.</p>
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
