<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LeadSequence;
use App\Services\NurtureSequenceService;
use Illuminate\Console\Command;

class ProcessNurtureSequences extends Command
{
    protected $signature = 'sequences:process';
    protected $description = 'Process due nurture sequence steps and send messages';

    public function handle(): int
    {
        $service   = new NurtureSequenceService();
        $processed = 0;

        // 1. Resume paused sequences where IA/Chatbot was removed
        $resumed = $service->resumePaused();

        // 2. Process due steps
        $due = LeadSequence::withoutGlobalScope('tenant')
            ->where('status', 'active')
            ->where('next_step_at', '<=', now())
            ->with(['lead', 'sequence'])
            ->limit(100)
            ->get();

        foreach ($due as $ls) {
            if (! $ls->sequence || ! $ls->lead) {
                $ls->markExited('manual');
                continue;
            }

            $service->processStep($ls);
            $processed++;
        }

        if ($processed > 0 || $resumed > 0) {
            $this->info("Sequences: {$processed} steps processed, {$resumed} resumed.");
        }

        return self::SUCCESS;
    }
}
