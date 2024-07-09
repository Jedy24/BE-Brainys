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

            return response()->json([
                'status' => 'success',
                'message' => 'Fase retrieved successfully',
                'data' => $fase,
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
        $fase = $request->input('fase');

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
        $fase = $request->input('fase');
        $mataPelajaran = $request->input('mata_pelajaran');

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
        $fase = $request->input('fase');
        $mataPelajaran = $request->input('mata_pelajaran');
        $element = $request->input('element');

        try {
            $finalData = CapaianPembelajaran::where('fase', $fase)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $element)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->distinct()
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Final data retrieved successfully',
                'data' => $finalData,
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
