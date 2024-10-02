<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
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
            padding: 0;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #f0f4f8;
            padding: 20px;
            text-align: center;
        }

        .header img {
            max-width: 150px;
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
            line-height: 1.5;
        }

        .text-base-no-space {
            font-size: 16px;
            color: #000000;
            line-height: 1;
        }

        .bold-text {
            font-weight: bold;
        }

        .footer {
            background-color: #f0f4f8;
            color: #888888;
            padding: 20px;
            text-align: center;
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
    </style>
    
    {{-- Additional styles can be added here --}}
    @yield('styles')
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="https://be.brainys.oasys.id/img/Logo@2x.png" alt="Brainys Logo">
        </div>

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="text-sm">© {{ date('Y') }} PT Oasys Edutech Indonesia</p>
        </div>
    </div>
</body>
</html>
