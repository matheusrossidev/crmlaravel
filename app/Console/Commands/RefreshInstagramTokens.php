<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshInstagramTokens extends Command
{
    protected $signature = 'instagram:refresh-tokens';
    protected $description = 'Refresh Instagram long-lived tokens that expire within 7 days';

    public function handle(): int
    {
        // Find tokens expiring in the next 7 days (or already expired up to 2 days ago)
        $instances = InstagramInstance::withoutGlobalScope('tenant')
            ->where('status', '!=', 'disconnected')
            ->where('token_expires_at', '<=', now()->addDays(7))
            ->where('token_expires_at', '>=', now()->subDays(2))
            ->get();

        if ($instances->isEmpty()) {
            $this->info('No tokens need refreshing.');
            return 0;
        }

        $this->info("Found {$instances->count()} token(s) to refresh.");

        foreach ($instances as $instance) {
            try {
                $currentToken = decrypt($instance->access_token);

                // Meta Graph API: refresh long-lived token
                // This endpoint generates a NEW long-lived token (60 more days)
                $response = Http::timeout(15)->get('https://graph.instagram.com/refresh_access_token', [
                    'grant_type'   => 'ig_refresh_token',
                    'access_token' => $currentToken,
                ]);

                if ($response->successful()) {
                    $data      = $response->json();
                    $newToken  = $data['access_token'] ?? null;
                    $expiresIn = $data['expires_in'] ?? 5184000; // default 60 days

                    if ($newToken) {
                        $instance->update([
                            'access_token'    => encrypt($newToken),
                            'token_expires_at' => now()->addSeconds((int) $expiresIn),
                            'status'          => 'connected',
                        ]);

                        $this->info("  ✓ {$instance->username} (tenant {$instance->tenant_id}) — refreshed, expires " . now()->addSeconds((int) $expiresIn)->format('d/m/Y'));

                        Log::channel('instagram')->info('Token refreshed', [
                            'instance_id' => $instance->id,
                            'tenant_id'   => $instance->tenant_id,
                            'username'    => $instance->username,
                            'expires_at'  => now()->addSeconds((int) $expiresIn)->toISOString(),
                        ]);
                    } else {
                        $this->warn("  ✗ {$instance->username} — no token in response");
                    }
                } else {
                    $body = $response->body();
                    $this->error("  ✗ {$instance->username} — HTTP {$response->status()}: {$body}");

                    // If token is completely invalid, mark as expired
                    if (str_contains($body, '"code":190') || str_contains($body, 'OAuthException')) {
                        $instance->update(['status' => 'expired']);
                        $this->warn("    → Marked as expired (needs manual reconnection)");
                    }

                    Log::channel('instagram')->warning('Token refresh failed', [
                        'instance_id' => $instance->id,
                        'tenant_id'   => $instance->tenant_id,
                        'status'      => $response->status(),
                        'body'        => $body,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ {$instance->username} — {$e->getMessage()}");
                Log::channel('instagram')->error('Token refresh exception', [
                    'instance_id' => $instance->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }
}
