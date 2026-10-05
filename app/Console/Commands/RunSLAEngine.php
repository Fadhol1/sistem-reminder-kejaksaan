<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SLAEngineService;

class RunSLAEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipeta:run-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengevaluasi semua timeline dan menjalankan Transisi Otomatis untuk SLA yang kedaluwarsa.';

    /**
     * Execute the console command.
     */
    public function handle(SLAEngineService $slaService)
    {
        $this->info('Memulai Proses Pengecekan SLA...');
        $slaService->executeEngine();
        $this->info('Selesai Melakukan Pemeriksaan SLA.');
    }
}
