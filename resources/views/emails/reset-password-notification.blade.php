<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Notification</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #e7edf3;
            color: #333333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
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
            max-width: 120px;
            height: auto;
        }

        .content {
            padding: 30px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #000000;
        }

        .text-base {
            font-size: 16px;
            color: #000000;
        }

        /* Styling button reset password */
        .reset-btn {
            display: inline-block;
            padding: 12px 12px;
            margin-top: 10px;
            background-color: #3758F9;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            font-weight: bold;
        }

        .reset-btn:hover {
            background-color: #0056b3;
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
            <p class="title">Permintaan Reset Password</p>
            <p class="text-base">Kami menerima permintaan untuk mengatur ulang kata sandi Brainys Anda.</p>

            <!-- Tombol Reset Password -->
            <div style="text-align: center;"> <!-- Tambahkan elemen div untuk memusatkan tombol -->
                <a href="{{ url('https://staging.brainys.oasys.id/forget-password?email=' . $user->email . '&token=' . $user->reset_token) }}" class="reset-btn">
                    Reset Password
                </a>
            </div>

            <p class="text-base">
                Namun jika Anda merasa tidak melakukan aktivitas ini, segera hubungi tim dukungan kami untuk bantuan lebih lanjut.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="text-sm">© 2024 PT Oasys Edutech Indonesia</p>
        </div>
    </div>
</body>

</html>
