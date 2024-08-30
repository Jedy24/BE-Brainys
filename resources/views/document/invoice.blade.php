<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $transactionData['transaction_code'] }}</title>
    <script src="{{ public_path('css/tailwind.css') }}"></script>
    <style>
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="print-page bg-white p-6 rounded-lg shadow-md">

        <table class="w-full table-fixed mb-6">
            <tr>
                <td class="w-1/2 p-4">
                    <img src="{{ public_path('img/Logo@2x.png') }}" alt="Logo" class="h-12">
                </td>
                <td class="w-1/2 p-4">
                    <h1 class="text-md font-bold">PT Oasys Edutech Indonesia</h1>
                    <p class="text-sm text-gray-600">Jl.Cikaso 86, Cibeunying Kidul, Kota Bandung, Jawa Barat,
                        Indonesia.</p>
                </td>
            </tr>
        </table>

        <div class="mb-4">
            <p class="text-lg font-semibold">Invoice #{{ $transactionData['transaction_code'] }}</p>
            <p class="text-sm text-gray-500 mb-2">Tanggal Invoice: {{ $transactionData['created_at_format'] }}</p>
            <span class="text-green-500 text-lg font-bold">DIBAYAR</span>
        </div>

        <div class="mb-4">
            <p class="font-semibold">Tujuan Invoice</p>
            <p class="text-sm text-gray-500">{{ $transactionData['user']['name'] }}</p>
            <p class="text-sm text-gray-500">Indonesia</p>
        </div>

        <table class="w-full mb-6 border-collapse border border-gray-300">
            <thead>
                <tr>
                    <th class="text-sm border border-gray-300 px-4 py-2 text-left">Deskripsi</th>
                    <th class="text-sm border border-gray-300 px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-sm border border-gray-300 px-4 py-2">Pembelian
                        {{ $transactionData['transaction_name'] }}</td>
                    <td class="text-sm border border-gray-300 px-4 py-2 text-right">
                        {{ $transactionData['amount_sub_format'] }}</td>
                </tr>
                <tr>
                    <td class="text-sm border border-gray-300 px-4 py-2">Biaya admin</td>
                    <td class="text-sm border border-gray-300 px-4 py-2 text-right">
                        {{ $transactionData['amount_fee_format'] }}</td>
                </tr>
                {{-- <tr>
                    <td class="text-sm border border-gray-300 px-4 py-2 font-semibold">Sub Total</td>
                    <td class="text-sm border border-gray-300 px-4 py-2 text-right font-semibold">{{ $transactionData['amount_total_format'] }}</td>
                </tr>
                <tr>
                    <td class="text-sm border border-gray-300 px-4 py-2">11,00% PPN</td>
                    <td class="text-sm border border-gray-300 px-4 py-2 text-right">Rp 136</td>
                </tr> --}}
                <tr>
                    <td class="text-sm border border-gray-300 px-4 py-2 font-bold">Total</td>
                    <td class="text-sm border border-gray-300 px-4 py-2 text-right font-bold">
                        {{ $transactionData['amount_total_format'] }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mb-6">
            <p class="font-semibold">Pembayaran</p>
            <p class="text-sm text-gray-500 text-justify"><span
                    class="font-semibold">{{ $transactionData['amount_total_format'] }}</span> telah dibayar pada
                tanggal
                {{ $transactionData['updated_at_format'] }} menggunakan
                {{ $transactionData['payment']['service_name'] }}</p>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-600">Terima kasih telah menggunakan layanan kami!</p>
            <a href="https://www.brainys.id" class="text-blue-500">www.brainys.id</a>
        </div>
    </div>
</body>

</html>
