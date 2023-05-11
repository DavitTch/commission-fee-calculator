<?php

namespace App\Console\Commands;

use App\Services\CommissionFeeCalculateService;
use Illuminate\Console\Command;

class CalculateFee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:fee {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates operations fee';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $filePath = storage_path($this->argument('filePath'));

        if (!file_exists($filePath)) {
            throw new \Exception('File not found in storage folder!');
        }

        $commissions = (new CommissionFeeCalculateService($filePath))->calculate();

        foreach ($commissions as $commission) {
            $this->line($commission);
        }
    }
}
