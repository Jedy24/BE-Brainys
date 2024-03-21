<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeedbackReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackReviewController extends Controller
{
    public function index()
    {
        try {
            // Mendapatkan pengguna yang terautentikasi saat ini
            $user = Auth::user();

            // Mengambil semua feedback reviews yang terkait dengan pengguna yang sedang login
            $feedbackReviews = $user->feedbackReviews;

            // Mengembalikan respons JSON dengan data feedback reviews
            return response()->json([
                'status' => 'success',
                'message' => 'Feedback reviews for logged-in user retrieved successfully',
                'data' => $feedbackReviews,
            ], 200);
        } catch (\Exception $e) {
            // Menangani jika terjadi kesalahan
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function show($id)
    {
        // Logic untuk menampilkan detail feedback review berdasarkan ID
    }

    public function store(Request $request)
    {
        try {
            // Mendapatkan pengguna yang terautentikasi saat ini
            $user = Auth::user();

            // Validasi data yang diterima dari request
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'product' => 'required|string',
                'message' => 'required|string',
            ]);

            // Membuat feedback review baru dengan menggunakan user_id dari pengguna yang terautentikasi
            $feedbackReview = FeedbackReview::create([
                'user_id' => $user->id,
                'rating' => $request->rating,
                'product' => $request->product,
                'message' => $request->message,
            ]);

            // Mengembalikan respons JSON dengan data feedback review yang baru dibuat
            return response()->json([
                'status' => 'success',
                'message' => 'Feedback review created successfully',
                'data' => $feedbackReview,
            ], 201);
        } catch (\Exception $e) {
            // Menangani jika terjadi kesalahan
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Logic untuk memperbarui feedback review berdasarkan ID
    }

    public function destroy($id)
    {
        // Logic untuk menghapus feedback review berdasarkan ID
    }
}
