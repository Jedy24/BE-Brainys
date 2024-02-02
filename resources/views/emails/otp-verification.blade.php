
@component('mail::message')
    ![Oasys Syllabus Logo]({{ asset('images/logo-oasys.png') }})

    # Oasys Syllabus
    Hello!

    Kode OTP untuk verifikasi adalah: **{{ $otp }}**

    @component('mail::button', ['url' => url('/verify-otp')])
        Verifikasi Sekarang
    @endcomponent

    Jika anda tidak meminta kode OTP, abaikan pesan ini.

    Regards,
    Oasys Syllabus
@endcomponent
