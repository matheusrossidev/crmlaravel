<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function index(): View
    {
        return view('master.system.index');
    }

    public function stats(): JsonResponse
    {
        $load = sys_getloadavg();

        return response()->json([
            'cpu_load'     => $load,
            'memory_php'   => memory_get_usage(true),
            'memory_peak'  => memory_get_peak_usage(true),
            'disk_free'    => disk_free_space('/') ?: 0,
            'disk_total'   => disk_total_space('/') ?: 0,
            'ram'          => $this->parseMeminfo(),
            'php_version'  => PHP_VERSION,
            'laravel_env'  => app()->environment(),
            'timestamp'    => now()->toISOString(),
        ]);
    }

    private function parseMeminfo(): array
    {
        if (! file_exists('/proc/meminfo')) {
            return [];
        }

        $raw   = (string) file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $raw, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $raw, $avail);

        return [
            'total_kb'     => (int) ($total[1] ?? 0),
            'available_kb' => (int) ($avail[1] ?? 0),
        ];
    }
}
