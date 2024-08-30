<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MemoryLimitCheckCommand extends Command
{
    // Nama command yang akan digunakan di terminal
    protected $signature = 'memory:check';

    // Deskripsi command
    protected $description = 'Check and increase memory limit';

    // Fungsi utama yang akan dijalankan saat command dipanggil
    public function handle()
    {
        // Menampilkan memory limit saat ini
        $currentLimit = ini_get('memory_limit');
        $this->info('Memory limit sebelum dinaikkan: ' . $currentLimit);

        // Menaikkan memory limit ke 1GB
        ini_set('memory_limit', '1024M');

        // Menampilkan memory limit setelah dinaikkan
        $newLimit = ini_get('memory_limit');
        $this->info('Memory limit setelah dinaikkan: ' . $newLimit);

        // Tambahkan logika command lainnya di sini

        $this->info('Command telah selesai.');
    }
}
