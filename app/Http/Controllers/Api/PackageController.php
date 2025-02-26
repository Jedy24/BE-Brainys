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
    public function getPackage(Request $request)
    {
        try {
            $packages = Package::with('details')->get();

            $groupedPackages = $packages->groupBy('type');

            $user = $request->user();

            $userPackages = $user->userPackages()->with('package')->first();
            $userPackageName = $userPackages?->package?->name;
            $userPackageType = $userPackages?->package?->type;
            $userCredit = $userPackages?->package?->credit_add_monthly;

            // Menentukan tombol berdasarkan kondisi user
            $getButtonStatus = function ($package) use ($userPackageName, $userPackageType, $userCredit) {
                if ($userPackageName === $package->name && $userPackageType === $package->type) {
                    return [
                        'is_disabled' => true,
                        'label' => 'Terpilih'
                    ];
                }

                // Jika paket free, bisa upgrade ke semua paket
                if ($userPackageType === 'free') {
                    return [
                        'is_disabled' => false,
                        'label' => 'Upgrade Paket'
                    ];
                }

                // Jika paket yang sedang di-loop lebih besar kreditnya
                if ($package->credit_add_monthly > $userCredit) {
                    // Jika user memiliki paket Monthly, bisa upgrade ke Monthly atau Annually yang lebih besar
                    if ($userPackageType === 'monthly' && ($package->type === 'monthly' || $package->type === 'annually')) {
                        return [
                            'is_disabled' => false,
                            'label' => 'Upgrade Paket'
                        ];
                    }
                    // Jika user memiliki paket Annually, hanya bisa upgrade ke Annually yang lebih besar
                    if ($userPackageType === 'annually' && $package->type === 'annually') {
                        return [
                            'is_disabled' => false,
                            'label' => 'Upgrade Paket'
                        ];
                    }
                }

                return [
                    'is_disabled' => true,
                    'label' => 'Pilih Paket'
                ];
            };

            // Generate paket free
            $freePackage = $groupedPackages->get('free', collect())->map(function ($package) use ($getButtonStatus) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'description' => $package->description,
                    'credit_add_monthly' => $package->credit_add_monthly,
                    'price' => $package->price,
                    'details' => $package->details->pluck('name')->toArray(),
                    'buttons' => $getButtonStatus($package),
                ];
            })->toArray();

            $data = [
                'monthly' => array_merge(
                    $freePackage,
                    $groupedPackages->get('monthly', collect())->map(function ($package) use ($getButtonStatus) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'type' => $package->type,
                            'description' => $package->description,
                            'credit_add_monthly' => $package->credit_add_monthly,
                            'price' => $package->price,
                            'details' => $package->details->pluck('name')->toArray(),
                            'buttons' => $getButtonStatus($package),
                        ];
                    })->toArray()
                ),
                'annually' => array_merge(
                    $freePackage,
                    $groupedPackages->get('annually', collect())->map(function ($package) use ($getButtonStatus) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'type' => $package->type,
                            'description' => $package->description,
                            'credit_add_monthly' => $package->credit_add_monthly,
                            'price' => $package->price,
                            'details' => $package->details->pluck('name')->toArray(),
                            'buttons' => $getButtonStatus($package),
                        ];
                    })->toArray()
                ),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Packages retrieved successfully',
                'data' => $data,
            ], 200);


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
