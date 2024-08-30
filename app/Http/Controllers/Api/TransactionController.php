<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();

            $transactions = Transaction::with('details')
                ->where('id_user', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $transactions->transform(function ($transaction) {
                $transaction->amount_sub = intval($transaction->amount_sub);
                $transaction->amount_fee = intval($transaction->amount_fee);
                $transaction->amount_total = intval($transaction->amount_total);

                $transaction->details->transform(function ($detail) {
                    return [
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'item_price' => intval($detail->item_price),
                        'item_qty' => $detail->item_qty,
                    ];
                });

                return $transaction;
            });

            $perPage = 8;
            $page = request('page', 1);
            $pagedData = $transactions->slice(($page - 1) * $perPage, $perPage)->values();

            $urlPrefix = 'https://be.brainys.oasys.id/api/';
            $pagedData = $pagedData->map(function ($item) use ($urlPrefix) {
                // $item['url_api_data'] = $urlPrefix . $item['type'] . '/subscription/history/' . $item['id'];
                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions retrieved successfully',
                'data' => $pagedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $transactions->count(),
                    'last_page' => ceil($transactions->count() / $perPage),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function invoice(Request $request)
    {
        ini_set('memory_limit', '1024M');

        try {
            $transactionId  = $request->input('id');
            $transaction    = Transaction::with(['user', 'details' => function ($query) {
                $query->first();
            }, 'payment' => function ($query) {
                $query->first();
            }])->find($transactionId);

            if (!$transaction) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Transaction not found',
                ], 404);
            }

            if (!in_array($transaction->status, ['success', 'completed'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice tidak bisa dibuat karena status belum terselesaikan',
                ], 400);
            }

            $pdfFileName = 'Invoice-Brainys-' . auth()->id() . '-' . $transaction->transaction_code . '.pdf';
            $outputPath = public_path('pdf_output/' . $pdfFileName);

            if (file_exists($outputPath)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Invoice PDF telah dibuata',
                    'data' => [
                        'output_path' => $outputPath,
                        'download_url' => url('pdf_output/' . $pdfFileName),
                    ],
                ], 200);
            }

            // Dapatkan detail pertama dan payment pertama
            $firstDetail = $transaction->details->first();
            $firstPayment = $transaction->payment->first();

            // Format tanggal dan waktu
            $createdAtFormatted = Carbon::parse($transaction->created_at)->isoFormat('dddd, D MMMM YYYY HH:mm:ss');
            $updatedAtFormatted = Carbon::parse($transaction->updated_at)->isoFormat('D MMMM YYYY');

            // Format uang ke format Rupiah
            $amountSubFormatted = 'Rp ' . number_format($transaction->amount_sub, 0, ',', '.');
            $amountFeeFormatted = 'Rp ' . number_format($transaction->amount_fee, 0, ',', '.');
            $amountTotalFormatted = 'Rp ' . number_format($transaction->amount_total, 0, ',', '.');

            // Hitung amount tax (11% dari amount total)
            $amountTax = $transaction->amount_total * 0.11;
            $amountTaxFormatted = 'Rp ' . number_format($amountTax, 0, ',', '.');

            // Hitung amount final (amount total + tax)
            $amountFinal = $transaction->amount_total + $amountTax;
            $amountFinalFormatted = 'Rp ' . number_format($amountFinal, 0, ',', '.');

            // Tambahkan data baru ke dalam x_data
            $transactionData = $transaction->toArray();
            $transactionData['details'] = $firstDetail->toArray();
            $transactionData['payment'] = $firstPayment->toArray();
            $transactionData['created_at_format'] = $createdAtFormatted;
            $transactionData['updated_at_format'] = $updatedAtFormatted;
            $transactionData['amount_sub_format'] = $amountSubFormatted;
            $transactionData['amount_fee_format'] = $amountFeeFormatted;
            $transactionData['amount_total_format'] = $amountTotalFormatted;
            $transactionData['amount_tax'] = $amountTax;
            $transactionData['amount_tax_format'] = $amountTaxFormatted;
            $transactionData['amount_final'] = $amountFinal;
            $transactionData['amount_final_format'] = $amountFinalFormatted;

            // Fetch Tailwind CSS from CDN
            $tailwindCss = Http::get('https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css')->body();

            // Render the Blade view to HTML
            $html = view('document.invoice', compact('transactionData'))->render();

            // Embed Tailwind CSS into the HTML
            $html = str_replace(
                '</head>',
                '<style>' . $tailwindCss . '</style></head>',
                $html
            );

            // Initialize DomPDF and set options
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'sans-serif');
            $options->set('chroot', public_path());

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');

            // Render PDF
            $dompdf->render();

            // Save the PDF to a file
            $outputPath = public_path('pdf_output/' . $pdfFileName);
            file_put_contents($outputPath, $dompdf->output());

            // Return the URL of the generated PDF
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice PDF telah dibuat',
                'data' => [
                    'output_path' => $outputPath,
                    'download_url' => url('pdf_output/' . $pdfFileName),
                ],
                // 'data_x' => $transactionData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function convertDocxToPdf($docxPath, $pdfPath)
    {
        try {
            // Ensure the DOCX file exists
            if (!file_exists($docxPath)) {
                throw new \Exception("DOCX file not found: " . $docxPath);
            }

            // Set up the PDF renderer settings for PHPWord with TCPDF
            $tcpdfPath = base_path('vendor/tecnickcom/tcpdf');
            \PhpOffice\PhpWord\Settings::setPdfRendererPath($tcpdfPath);
            \PhpOffice\PhpWord\Settings::setPdfRendererName('TCPDF');

            // Load the DOCX file with PHPWord
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);

            // Save the content as a PDF
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($pdfPath);

            // Return the path to the generated PDF
            return $pdfPath;
        } catch (\Exception $e) {
            // Handle any errors that occurred during the conversion
            throw new \Exception("Error converting DOCX to PDF: " . $e->getMessage());
        }
    }
}
