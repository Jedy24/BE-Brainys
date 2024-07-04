<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\CapaianPembelajaran;
use Illuminate\Http\Request;

class CapaianPembelajaranController extends Controller
{
    public function getMataPelajaran(Request $request)
    {
        try {
            $mataPelajaran = CapaianPembelajaran::select('mata_pelajaran')->distinct()->get();

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

    public function getFase(Request $request)
    {
        $mataPelajaran = $request->input('mata_pelajaran');

        try {
            $fase = CapaianPembelajaran::where('mata_pelajaran', $mataPelajaran)
                ->select('fase')
                ->distinct()
                ->get();

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

    public function getElement(Request $request)
    {
        $mataPelajaran = $request->input('mata_pelajaran');
        $fase = $request->input('fase');

        try {
            $element = CapaianPembelajaran::where('mata_pelajaran', $mataPelajaran)
                ->where('fase', $fase)
                ->select('element')
                ->distinct()
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Element retrieved successfully',
                'data' => $element,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function getSubElement(Request $request)
    {
        $mataPelajaran = $request->input('mata_pelajaran');
        $fase = $request->input('fase');
        $element = $request->input('element');

        try {
            $subElement = CapaianPembelajaran::where('mata_pelajaran', $mataPelajaran)
                ->where('fase', $fase)
                ->where('element', $element)
                ->select('subelemen', 'capaian_pembelajaran')
                ->distinct()
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'SubElement retrieved successfully',
                'data' => $subElement,
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
