<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        $logDir = storage_path('logs');
        $files  = glob($logDir . '/*.log') ?: [];

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        $files = array_map(fn ($f) => [
            'name'     => basename($f),
            'size'     => $this->formatBytes(filesize($f)),
            'modified' => date('d/m/Y H:i', filemtime($f)),
        ], $files);

        return view('master.logs.index', compact('files'));
    }

    public function content(Request $request): JsonResponse
    {
        $filename = basename((string) $request->input('file', ''));

        if (! $filename || ! str_ends_with($filename, '.log')) {
            return response()->json(['error' => 'Arquivo inválido.'], 400);
        }

        $path = storage_path('logs/' . $filename);

        if (! file_exists($path)) {
            return response()->json(['error' => 'Arquivo não encontrado.'], 404);
        }

        $lines   = max(50, min(1000, (int) $request->input('lines', 200)));
        $content = $this->tailFile($path, $lines);

        return response()->json(['content' => $content, 'file' => $filename]);
    }

    private function tailFile(string $path, int $lines): string
    {
        // Lê últimas N linhas sem carregar o arquivo inteiro na memória
        $fp   = fopen($path, 'r');
        $buf  = [];
        $size = filesize($path);

        if ($size === 0 || $fp === false) {
            return '';
        }

        fseek($fp, max(0, $size - $lines * 300));
        $raw = fread($fp, $size);
        fclose($fp);

        $all    = explode("\n", (string) $raw);
        $result = array_slice($all, max(0, count($all) - $lines));

        return implode("\n", $result);
    }

    private function formatBytes(int|false $bytes): string
    {
        if ($bytes === false) return '?';
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
