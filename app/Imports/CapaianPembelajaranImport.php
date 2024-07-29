<?php

namespace App\Imports;

use App\Models\CapaianPembelajaran;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CapaianPembelajaranImport implements ToModel, WithHeadingRow
{
    public $successCount = 0;
    public $failureCount = 0;
    public $totalRows = 0;
    public $failures = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        ++$this->totalRows;

        // Remove new lines from strings
        $fase = $row['fase'] ?? null;
        $mata_pelajaran =  isset($row['mata_pelajaran']) ? preg_replace('/\r\n|\r|\n/', ' ', $row['mata_pelajaran']) : null;
        $element = isset($row['element']) ? preg_replace('/\r\n|\r|\n/', ' ', $row['element']) : null;
        $subelemen = isset($row['subelemen']) ? preg_replace('/\r\n|\r|\n/', ' ', $row['subelemen']) : null;
        $capaian_pembelajaran = isset($row['capaian_pembelajaran']) ? preg_replace('/\r\n|\r|\n/', ' ', $row['capaian_pembelajaran']) : null;
        $capaian_pembelajaran_redaksi = isset($row['capaian_pembelajaran_redaksi']) ? preg_replace('/\r\n|\r|\n/', ' ', $row['capaian_pembelajaran_redaksi']) : null;

        $data = [
            'mata_pelajaran' => $mata_pelajaran,
            'fase' => $fase,
            'element' => $element,
            'subelemen' => $subelemen,
            'capaian_pembelajaran' => $capaian_pembelajaran,
            'capaian_pembelajaran_redaksi' => $capaian_pembelajaran_redaksi,
        ];

        $validator = Validator::make($data, [
            'mata_pelajaran' => 'required|string',
            'fase' => 'required|string',
            'element' => 'required|string',
            'subelemen' => 'required|string',
            'capaian_pembelajaran' => 'required|string',
            'capaian_pembelajaran_redaksi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ++$this->failureCount;
            $this->failures[] = [
                'row' => $this->totalRows,
                'errors' => $validator->errors()->all(),
                'values' => $data,
            ];
            return null;
        }

        $existingRecord = CapaianPembelajaran::where('mata_pelajaran', $mata_pelajaran)
            ->where('fase', $fase)
            ->where('element', $element)
            ->where('subelemen', $subelemen)
            ->where('capaian_pembelajaran', $capaian_pembelajaran)
            ->where('capaian_pembelajaran_redaksi', $capaian_pembelajaran_redaksi)
            ->first();

        if ($existingRecord) {
            ++$this->failureCount;
            $this->failures[] = [
                'row' => $this->totalRows,
                'errors' => ['Duplicate record found'],
                'values' => $data,
            ];
            return null;
        }

        ++$this->successCount;

        return new CapaianPembelajaran($data);
    }
}