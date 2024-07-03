<?php

namespace App\Imports;

use App\Models\CapaianPembelajaran;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class CapaianPembelajaranImport implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    public $successCount = 0;
    public $failureCount = 0;
    public $totalRows = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        ++$this->totalRows;

        try {
            $existingRecord = CapaianPembelajaran::where('mata_pelajaran', $row['mata_pelajaran'] ?? null)
                ->where('fase', $row['fase'] ?? null)
                ->where('element', $row['element'] ?? null)
                ->where('subelemen', $row['subelemen'] ?? null)
                ->where('capaian_pembelajaran', $row['capaian_pembelajaran'] ?? null)
                ->first();

            if ($existingRecord) {
                ++$this->failureCount;
                return null; // Skip row if it already exists
            }

            $capaianPembelajaran = new CapaianPembelajaran([
                'mata_pelajaran' => $row['mata_pelajaran'] ?? null,
                'fase' => $row['fase'] ?? null,
                'element' => $row['element'] ?? null,
                'subelemen' => $row['subelemen'] ?? null,
                'capaian_pembelajaran' => $row['capaian_pembelajaran'] ?? null,
            ]);

            if ($capaianPembelajaran->mata_pelajaran && $capaianPembelajaran->fase && $capaianPembelajaran->element && $capaianPembelajaran->capaian_pembelajaran) {
                ++$this->successCount;
                return $capaianPembelajaran;
            } else {
                ++$this->failureCount;
                return null; // Skip row if any required field is missing
            }
        } catch (\Exception $e) {
            ++$this->failureCount;
            Log::error('Error importing row: ' . $e->getMessage());
            return null; // Skip row if an exception occurs
        }
    }

    public function onFailure(Failure ...$failures)
    {
        $this->failureCount += count($failures);
    }
}
