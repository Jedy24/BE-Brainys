<?php

namespace App\Console\Commands;

use App\Imports\CapaianPembelajaranImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportCapaianPembelajaran extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import:capaian-pembelajaran {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Capaian Pembelajaran from an Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error('File does not exist.');
            return 1;
        }

        try {
            $import = new CapaianPembelajaranImport;
            Excel::import($import, $file);

            if (!empty($import->failures)) {
                $this->info('Failed Rows:');
                foreach ($import->failures as $failure) {
                    $this->info('Row: ' . $failure['row']);
                    $this->info('Errors: ' . implode(', ', $failure['errors']));
                    // $this->info('Values: ' . json_encode($failure['values']));
                }
            }

            $this->info('Data imported successfully.');
            $this->info('Total Rows: ' . $import->totalRows);
            $this->info('Total Successful: ' . $import->successCount);
            $this->info('Total Failed: ' . $import->failureCount);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error during import: ' . $e->getMessage());
            return 1;
        }
    }
}
