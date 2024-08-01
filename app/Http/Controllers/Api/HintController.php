<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HintHistories;
use App\Models\CapaianPembelajaran;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HintController extends Controller
{
    private $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function generate(Request $request)
    {
        try {
            // Input validation
            $request->validate([
                'name'  =>'required',
                'pokok_materi' => 'required',
                'grade' => 'required',
                'subject' => 'required',
                'elemen_capaian' => 'required',
                'jumlah_soal' => 'required|integer',
                'notes' => 'nullable',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat kisi-kisi.',
                ], 400);
            }

            // Check if the user has less than 20 limit generate
            if ($user->generateAllSum() >= $user->limit_generate) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat kisi-kisi.',
                    'data' => [
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaKisiKisi   = $request->input('name');
            $pokokMateri    = $request->input('pokok_materi');
            // $tingkatKelas   = $request->input('grade');
            $faseRaw        = $request->input('grade');
            $faseSplit      = explode('|', $faseRaw);
            $tingkatKelas   = trim($faseSplit[0]);
            $kelas          = trim($faseSplit[1]);
            $mataPelajaran  = $request->input('subject');
            $elemenCapaian  = $request->input('elemen_capaian');
            $jumlahSoal     = $request->input('jumlah_soal');
            $addNotes       = $request->input('notes');

            $finalData = CapaianPembelajaran::where('fase', $tingkatKelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', 'LIKE', '%' . implode('%', explode(' ', $elemenCapaian)) . '%')
                ->select('capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->first();

            if (!$finalData) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ], 400);
            }

            $capaianPembelajaranRedaksi = $finalData->capaian_pembelajaran_redaksi;

            $prompt         = $this->prompt($pokokMateri, $tingkatKelas, $mataPelajaran, $elemenCapaian, $jumlahSoal, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);
            $user = $request->user();

            $faseToKelas = [
                'Fase A' => 'Kelas 1 - 2 SD',
                'Fase B' => 'Kelas 3 - 4 SD',
                'Fase C' => 'Kelas 5 - 6 SD',
                'Fase D' => 'Kelas 7 - 9 SMP',
                'Fase E' => 'Kelas 10 SMA',
                'Fase F' => 'Kelas 11 - 12 SMA'
            ];

            $kelas = isset($faseToKelas[$tingkatKelas]) ? "{$tingkatKelas} ({$faseToKelas[$tingkatKelas]})" : $tingkatKelas;

            $parsedResponse['informasi_umum']['nama_kisi_kisi']                  = $namaKisiKisi;
            $parsedResponse['informasi_umum']['penyusun']                        = $user->name;
            $parsedResponse['informasi_umum']['instansi']                        = $user->school_name;
            $parsedResponse['informasi_umum']['elemen_capaian']                  = $elemenCapaian;
            $parsedResponse['informasi_umum']['pokok_materi']                    = $pokokMateri;
            $parsedResponse['informasi_umum']['kelas']                           = $kelas;
            $parsedResponse['informasi_umum']['mata_pelajaran']                  = $mataPelajaran;
            $parsedResponse['informasi_umum']['jumlah_soal']                     = $jumlahSoal;
            $parsedResponse['informasi_umum']['capaian_pembelajaran_redaksi']    = $capaianPembelajaranRedaksi;
            $parsedResponse['informasi_umum']['tahun_penyusunan']                = Date('Y');

            // Construct the response data for success
            $insertData = HintHistories::create([
                'name' => $namaKisiKisi,
                'pokok_materi' => $pokokMateri,
                'grade' => $tingkatKelas,
                'subject' => $mataPelajaran,
                'elemen_capaian' => $elemenCapaian,
                'jumlah_soal' => $jumlahSoal,
                'notes' => $addNotes,
                'generate_output' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $insertData->id;

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Kisi-kisi berhasil dihasilkan',
                'data' => $parsedResponse,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function convertToWord(Request $request)
    {
        try {
            $templatePath   = public_path('word_template/Kisi_Kisi_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Kisi_Kisi_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $hintHistoryId  = $request->input('id');
            $hintHistory     = HintHistories::find($hintHistoryId);

            $data = $hintHistory->generate_output;
            $docxTemplate->merge($data, $outputPath, false, false);

            // Assuming the merge operation is successful
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen word berhasil dibuat',
                'data' => ['output_path' => $outputPath, 'download_url' => url('word_output/' . basename($outputPath))],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function convertToExcel(Request $request)
    {
        try {
            $hintHistoryId = $request->input('id');
            if (!is_numeric($hintHistoryId)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'ID tidak valid.',
                ], 400);
            }

            $user = $request->user();
            $hintHistory = $user->hintHistory()->find($hintHistoryId);

            if (!$hintHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil kisi-kisi tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            $data = $hintHistory->generate_output;

            // Path template Excel
            $templatePath = public_path('excel_template/Kisi_Kisi_Template.xlsx');

            // Load template Excel
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Mengisi data ke dalam template sesuai dengan format yang diberikan
            $sheet->setCellValue('E3', $data['informasi_umum']['tahun_penyusunan']);
            $sheet->setCellValue('C6', $data['informasi_umum']['instansi']);
            $sheet->setCellValue('C7', $data['informasi_umum']['mata_pelajaran']);
            $sheet->setCellValue('C8', $data['informasi_umum']['kelas']);
            $sheet->setCellValue('F7', $data['informasi_umum']['jumlah_soal']);
            $sheet->setCellValue('F9', $data['informasi_umum']['penyusun']);
            $sheet->setCellValue('C13', $data['informasi_umum']['capaian_pembelajaran_redaksi']);
            $sheet->setCellValue('C14', $data['informasi_umum']['elemen_capaian']);
            $sheet->setCellValue('C15', $data['informasi_umum']['pokok_materi']);

            // Mengisi data kisi-kisi
            $templateRow = 17;
            $rowCount = count($data['kisi_kisi']);
            $highestRow = $templateRow + $rowCount - 1;

            for ($row = $templateRow; $row <= $highestRow; $row++) {
                if ($row != $templateRow) {
                    $sheet->duplicateStyle($sheet->getStyle('B' . $templateRow . ':F' . $templateRow), 'B' . $row . ':F' . $row);
                }

                $sheet->mergeCells("C{$row}:E{$row}");

                $sheet->setCellValue("B{$row}", $data['kisi_kisi'][$row - $templateRow]['nomor']);
                $sheet->setCellValue("C{$row}", $data['kisi_kisi'][$row - $templateRow]['indikator_soal']);
                $sheet->setCellValue("F{$row}", $data['kisi_kisi'][$row - $templateRow]['no_soal']);

                // Mengatur tinggi baris dan wrapping text
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $sheet->getStyle("B{$row}:F{$row}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }

            // Menyimpan spreadsheet ke file baru
            $fileName = 'Kisi_Kisi_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.xlsx';
            $filePath = public_path('excel_output/' . $fileName);

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen Excel berhasil dibuat',
                'data' => ['output_path' => $filePath, 'download_url' => url('excel_output/' . $fileName)],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Get hint histories for the authenticated user
            $hintHistories = $user->hintHistory()
                ->select(['id', 'name', 'pokok_materi', 'grade', 'subject', 'elemen_capaian', 'jumlah_soal', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($hintHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat kisi-kisi untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated hints by the user
            $generatedNum = $hintHistories->count();

            // Return the response with hint histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat kisi-kisi ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $hintHistories,
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

    public function historyDetail(Request $request, $id)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Get a specific hint history by ID for the authenticated user
            $hintHistory = $user->hintHistory()->find($id);

            if (!$hintHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil kisi-kisi tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with hint history data
            return response()->json([
                'status' => 'success',
                'message' => 'Hint history retrieved successfully',
                'data' => $hintHistory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function prompt($pokok_materi, $subject, $grade, $elemen_capaian, $jumlah_soal, $addNotes)
    {
        $prompt = "Tolong buatkan kisi-kisi untuk pokok materi: {$pokok_materi}, mata pelajaran: {$subject}, tingkat kelas: {$grade}, elemen capaian {$elemen_capaian}, dengan memperhatikan jumlah soal: {$jumlah_soal} dan catatan khusus: {$addNotes}. " . PHP_EOL .
            "Perhatian: Mohon jawab sesuai dengan format JSON berikut tanpa mengubah struktur format:" . PHP_EOL .
            '{' . PHP_EOL .
            '    "informasi_umum": {' . PHP_EOL .
            '        "penyusun": "",' . PHP_EOL .
            '        "instansi": "",' . PHP_EOL .
            '        "kelas": "",' . PHP_EOL .
            '        "mata_pelajaran": "",' . PHP_EOL .
            '        "jumlah_soal": 0,' . PHP_EOL .
            '        "capaian_pembelajaran_redaksi": "",' . PHP_EOL .
            '        "elemen_capaian": "",' . PHP_EOL .
            '        "pokok_materi": "",' . PHP_EOL .
            '        "tahun_penyusunan": ""' . PHP_EOL .
            '    },' . PHP_EOL .
            '    "kisi_kisi": [' . PHP_EOL .
            '        {' . PHP_EOL .
            '            "nomor": 0,' . PHP_EOL .
            '            "indikator_soal": "(Tujuan yang ingin dicapai oleh peserta didik dalam menyelesaikan persoalan yang diberikan. (Misalkan: Peserta didik mampu ... / Diberikan soal tentang ... / Format lain yang sesuai dengan soal yang diberikan. Minimal ada 2 format yang digunakan).",' . PHP_EOL .
            '            "no_soal": 0' . PHP_EOL .
            '        }' . PHP_EOL .
            '    ]' . PHP_EOL .
            '}' . PHP_EOL .
            "Pastikan Anda memisahkan informasi umum dan kisi-kisi ke dalam objek JSON yang terpisah seperti yang dicontohkan di atas." . PHP_EOL .
            "Berikan kisi_kisi sesuai dengan jumlah soal yang diberikan. Misalnya, jika jumlah soal adalah 5, maka jumlah objek dalam kisi_kisi juga harus 5." . PHP_EOL .
            "Terima kasih atas kerja sama Anda.";

        return $prompt;
    }

}
