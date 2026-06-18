<?php

namespace App\Console\Commands;

use App\Services\LeaveQuotaService;
use Illuminate\Console\Command;

class ProcessLeaveQuotaYearEnd extends Command
{
    protected $signature = 'leave-quota:year-end-closeout {year? : Tahun yang akan di-process}';
    protected $description = 'Proses carry forward dan hangus cuti berdasarkan anniversary periode cuti';

    protected LeaveQuotaService $quotaService;

    public function __construct(LeaveQuotaService $quotaService)
    {
        parent::__construct();
        $this->quotaService = $quotaService;
    }

    public function handle()
    {
        $year = $this->argument('year') ?? (now()->year - 1);

        $this->info("Memproses year-end closeout untuk tahun {$year}...");

        if ($this->confirm(
            'Anda yakin ingin memproses tahun ' . $year .
            '? Data akan di-lock dan dibawa ke tahun ' . ($year + 1) . '.'
        )) {
            try {
                $report = $this->quotaService->processYearEndCloseout($year);

                $this->info('');
                $this->info('✅ Year-end closeout berhasil!');
                $this->line('Laporan:');
                $this->line("  • Kuota yang diproses: {$report['processed_count']}");
                $this->line("  • Hari yang hangus: {$report['expired_total']} hari");

            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->info('Dibatalkan.');
        }

        return Command::SUCCESS;
    }
}
