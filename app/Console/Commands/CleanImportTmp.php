<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Limpa arquivos temporários de import de leads mais antigos que 1h.
 *
 * Usuários que abandonam o wizard de import no meio (upload feito mas
 * nunca executaram o step 2) deixam arquivos em storage/app/tmp/imports/.
 * Cron roda de hora em hora pra não acumular lixo.
 */
class CleanImportTmp extends Command
{
    protected $signature = 'import:cleanup-tmp {--dry-run : Só mostra o que apagaria}';

    protected $description = 'Limpa arquivos tmp de import de leads com mais de 1h.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $dir  = 'tmp/imports';

        if (! $disk->exists($dir)) {
            $this->info('Diretório não existe — nada pra limpar.');
            return self::SUCCESS;
        }

        $dry     = (bool) $this->option('dry-run');
        $cutoff  = now()->subHour()->timestamp;
        $deleted = 0;
        $kept    = 0;

        foreach ($disk->files($dir) as $file) {
            $lastModified = $disk->lastModified($file);
            if ($lastModified < $cutoff) {
                $this->line('  apagando: ' . $file);
                if (! $dry) {
                    $disk->delete($file);
                }
                $deleted++;
            } else {
                $kept++;
            }
        }

        $this->info(sprintf(
            '%s — %d apagados, %d mantidos (< 1h).',
            $dry ? 'DRY RUN' : 'Cleanup concluído',
            $deleted,
            $kept,
        ));

        return self::SUCCESS;
    }
}
