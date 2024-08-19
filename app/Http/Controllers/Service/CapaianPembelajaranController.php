<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\CapaianPembelajaran;
use Illuminate\Http\Request;

class CapaianPembelajaranController extends Controller
{
    public function getFase(Request $request)
    {
        try {
            $fase = CapaianPembelajaran::select('fase')->distinct()->get();

            $faseArray = [
                [
                    'fase' => 'Fase A | Kelas 1 - 2 SD',
                ],
                [
                    'fase' => 'Fase B | Kelas 3 - 4 SD',
                ],
                [
                    'fase' => 'Fase C | Kelas 5 - 6 SD',
                ],
                [
                    'fase' => 'Fase D | Kelas 7 - 9 SMP',
                ],
                [
                    'fase' => 'Fase E | Kelas 10 SMA/SMK',
                ],
                [
                    'fase' => 'Fase F | Kelas 11 - 12 SMA/SMK',
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Fase retrieved successfully',
                'data' => $faseArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function getMataPelajaran(Request $request)
    {
        $faseRaw    = $request->input('fase');
        $faseSplit  = explode('|', $faseRaw);
        $fase       = trim($faseSplit[0]);
        $kelas      = trim($faseSplit[1]);

        try {
            $mataPelajaran = CapaianPembelajaran::where('fase', $fase)
                ->select('mata_pelajaran')
                ->distinct()
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Mata Pelajaran retrieved successfully',
                'data' => $mataPelajaran,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function getElement(Request $request)
    {
        $faseRaw        = $request->input('fase');
        $faseSplit      = explode('|', $faseRaw);
        $fase           = trim($faseSplit[0]);
        $kelas          = trim($faseSplit[1]);
        $mataPelajaran  = $request->input('mata_pelajaran');

        try {
            $elements = CapaianPembelajaran::where('fase', $fase)
                ->where('mata_pelajaran', $mataPelajaran)
                ->select('element')
                ->distinct()
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Elements retrieved successfully',
                'data' => $elements,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function getFinalData(Request $request)
    {
        $faseRaw        = $request->input('fase');
        $faseSplit      = explode('|', $faseRaw);
        $fase           = trim($faseSplit[0]);
        $kelas          = trim($faseSplit[1]);
        $mataPelajaran  = $request->input('mata_pelajaran');
        $element        = $request->input('element');

        try {
            $finalData = CapaianPembelajaran::where('fase', $fase)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $element)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->get();

            if ($finalData->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No data found',
                    'data' => [],
                ]);
            }

            // Merge capaian_pembelajaran and get the first capaian_pembelajaran_redaksi
            $mergedCapaianPembelajaran = $finalData->pluck('capaian_pembelajaran')->implode(' ');
            $firstCapaianPembelajaranRedaksi = $finalData->first()->capaian_pembelajaran_redaksi;

            $result = [
                'fase' => $faseRaw,
                'mata_pelajaran' => $mataPelajaran,
                'element' => $element,
                'capaian_pembelajaran' => $mergedCapaianPembelajaran,
                'capaian_pembelajaran_redaksi' => $firstCapaianPembelajaranRedaksi,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Final data retrieved successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}
