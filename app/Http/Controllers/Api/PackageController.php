<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Get package data along with details.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPackage()
    {
        try {
            // Ambil semua package dengan detail
            $packages = Package::with('details')->get();

            // Pisahkan packages berdasarkan tipe
            $groupedPackages = $packages->groupBy('type');

            // Ambil paket dengan type 'free'
            $freePackage = $groupedPackages->get('free', collect())->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'description' => $package->description,
                    'credit_add_monthly' => $package->credit_add_monthly,
                    'price' => $package->price,
                    'details' => $package->details->pluck('name')->toArray(),
                ];
            })->toArray();

            // Bentuk struktur data yang diinginkan dengan free package di depan
            $data = [
                'monthly' => array_merge(
                    $freePackage, 
                    $groupedPackages->get('monthly', collect())->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'type' => $package->type,
                            'description' => $package->description,
                            'credit_add_monthly' => $package->credit_add_monthly,
                            'price' => $package->price,
                            'details' => $package->details->pluck('name')->toArray(),
                        ];
                    })->toArray()
                ),
                'annually' => array_merge(
                    $freePackage, 
                    $groupedPackages->get('annually', collect())->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'type' => $package->type,
                            'description' => $package->description,
                            'credit_add_monthly' => $package->credit_add_monthly,
                            'price' => $package->price,
                            'details' => $package->details->pluck('name')->toArray(),
                        ];
                    })->toArray()
                ),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Packages retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve packages: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
