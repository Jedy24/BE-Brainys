<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

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
        try {
            $templatePath   = public_path('word_template/Invoice_Brainys.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Invoice_Brainys_' . auth()->id() . '-' . md5(time() . '' . rand(1000, 9999)) . '.docx');
            $outputPdfPath  = public_path('word_output/Invoice_Brainys_' . auth()->id() . '-' . md5(time() . '' . rand(1000, 9999)) . '.pdf');
            $htmlPath       = public_path('word_output/Invoice_Brainys_' . auth()->id() . '-' . md5(time() . '' . rand(1000, 9999)) . '.html');

            $transactionId  = $request->input('id');
            $transaction    = Transaction::with(['user', 'details' => function ($query) {
                $query->first(); // Ambil hanya entri pertama dari details
            }, 'payment' => function ($query) {
                $query->first(); // Ambil hanya entri pertama dari payment
            }])->find($transactionId);

            if (!$transaction) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Transaction not found',
                ], 404);
            }

            if (!in_array($transaction->status, ['success', 'completed'])) {
                // return response()->json([
                //     'status' => 'failed',
                //     'message' => 'Invoice tidak bisa dibuat karena status belum terselesaikan',
                // ], 400);
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
            $transactionData['details'] = $firstDetail->toArray();  // Ubah details menjadi hanya detail pertama
            $transactionData['payment'] = $firstPayment->toArray(); // Ubah payment menjadi hanya payment pertama
            $transactionData['created_at_format'] = $createdAtFormatted;
            $transactionData['updated_at_format'] = $updatedAtFormatted;
            $transactionData['amount_sub_format'] = $amountSubFormatted;
            $transactionData['amount_fee_format'] = $amountFeeFormatted;
            $transactionData['amount_total_format'] = $amountTotalFormatted;
            $transactionData['amount_tax'] = $amountTax;
            $transactionData['amount_tax_format'] = $amountTaxFormatted;
            $transactionData['amount_final'] = $amountFinal;
            $transactionData['amount_final_format'] = $amountFinalFormatted;

            // Lakukan operasi merge jika diperlukan
            $docxTemplate->merge($transactionData, $outputPath, false, false);

            // Convert the DOCX to PDF using the new method
            $this->convertDocxToPdf($templatePath, $outputPdfPath);

            // Return response
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice telah diterbitkan',
                'data' => [
                    'output_path' => $outputPdfPath,
                    'download_url' => url('word_output/' . basename($outputPdfPath)),
                ],
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
