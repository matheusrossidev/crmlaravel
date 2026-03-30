<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MasterWhatsappNotifier;
use Illuminate\Console\Command;

class SendWeeklyReport extends Command
{
    protected $signature = 'master:weekly-report';

    protected $description = 'Envia relatório semanal da plataforma no grupo WhatsApp do master';

    public function handle(): int
    {
        MasterWhatsappNotifier::weeklyReport();

        $this->info('Relatório semanal enviado.');

        return self::SUCCESS;
    }
}
