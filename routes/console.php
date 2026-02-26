<?php

use App\Console\Commands\AnalyzeConversations;
use App\Console\Commands\AiFollowUpCommand;
use App\Console\Commands\SyncCampaignsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SyncCampaignsCommand::class)->hourly();
Schedule::command(AiFollowUpCommand::class)->everyTenMinutes();
Schedule::command(AnalyzeConversations::class)->everyThirtyMinutes();
