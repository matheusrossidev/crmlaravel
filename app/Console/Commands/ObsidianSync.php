<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Auto-gera notas Obsidian em obsidian-vault/30 Models/ + 40 Services/ + 60 Operations/Routes.md
 * a partir do código real (PHP reflection + Artisan route:list).
 *
 * Idempotente: sempre sobrescreve as notas geradas, marcando-as com `auto_generated: true`
 * no frontmatter pra distinguir das notas escritas a mão.
 *
 * Notas escritas a mão (em 10 Modules, 20 Architecture, 50 Bugs & Decisions, etc)
 * NUNCA são tocadas por esse comando.
 *
 * Roda:
 *   php artisan obsidian:sync
 *   php artisan obsidian:sync --models    # so models
 *   php artisan obsidian:sync --services  # so services
 *   php artisan obsidian:sync --routes    # so rotas
 */
class ObsidianSync extends Command
{
    protected $signature = 'obsidian:sync
                            {--models : Regenerar so notas de models}
                            {--services : Regenerar so notas de services}
                            {--routes : Regenerar so a nota de rotas}';

    protected $description = 'Regenera notas Obsidian em obsidian-vault/ a partir do codigo real';

    private string $vaultPath;

    public function handle(): int
    {
        $this->vaultPath = base_path('obsidian-vault');

        if (! is_dir($this->vaultPath)) {
            $this->error("Vault nao encontrado: {$this->vaultPath}");
            return self::FAILURE;
        }

        $only = match (true) {
            (bool) $this->option('models')   => 'models',
            (bool) $this->option('services') => 'services',
            (bool) $this->option('routes')   => 'routes',
            default                          => 'all',
        };

        if ($only === 'all' || $only === 'models') {
            $this->info('=== Regenerando 30 Models/ ===');
            $this->syncModels();
        }

        if ($only === 'all' || $only === 'services') {
            $this->info('=== Regenerando 40 Services/ ===');
            $this->syncServices();
        }

        if ($only === 'all' || $only === 'routes') {
            $this->info('=== Regenerando 60 Operations/Routes.md ===');
            $this->syncRoutes();
        }

        $this->info('');
        $this->info('OK. Para ver o resultado, abra o vault em obsidian-vault/');

        return self::SUCCESS;
    }

    // ── Models ───────────────────────────────────────────────────────────────

    private function syncModels(): void
    {
        $modelsDir = $this->vaultPath . '/30 Models';
        if (! is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }

        $finder = (new Finder())
            ->files()
            ->in(app_path('Models'))
            ->name('*.php')
            ->depth('< 3');

        $count = 0;
        foreach ($finder as $file) {
            $relPath = str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file->getRealPath());
            $class   = $this->classFromPath($file->getRealPath(), 'App\\Models\\');

            if (! $class || ! class_exists($class)) {
                continue;
            }

            // Pular trait, interface, abstract
            $ref = new ReflectionClass($class);
            if ($ref->isAbstract() || $ref->isInterface() || $ref->isTrait()) {
                continue;
            }

            $name = $ref->getShortName();
            $note = $this->renderModelNote($ref, $relPath);
            $path = $modelsDir . '/' . $name . '.md';
            file_put_contents($path, $note);
            $count++;
        }
        $this->line("  {$count} models regenerados");
    }

    private function renderModelNote(ReflectionClass $ref, string $relPath): string
    {
        $name = $ref->getShortName();

        // Traits
        $traits = array_map(
            fn ($t) => (new ReflectionClass($t))->getShortName(),
            $ref->getTraitNames(),
        );

        // Tabela (tentar instanciar)
        $table = '?';
        $fillable = [];
        $casts = [];
        try {
            $instance = $ref->newInstanceWithoutConstructor();
            if (method_exists($instance, 'getTable')) {
                $table = $instance->getTable();
            }
            $prop = $ref->getProperty('fillable');
            $prop->setAccessible(true);
            $fillable = (array) $prop->getValue($instance);
            $prop = $ref->getProperty('casts');
            $prop->setAccessible(true);
            $casts = (array) $prop->getValue($instance);
        } catch (\Throwable) {
        }

        // Relações: heurística — qualquer método público sem args que retorna BelongsTo/HasMany/etc
        $relations = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            if ($m->getNumberOfParameters() > 0) {
                continue;
            }
            $rt = $m->getReturnType();
            if ($rt instanceof \ReflectionNamedType) {
                $rtName = $rt->getName();
                if (str_contains($rtName, 'Eloquent\\Relations\\')) {
                    $shortRt = (new ReflectionClass($rtName))->getShortName();
                    $relations[] = ['method' => $m->getName(), 'kind' => $shortRt];
                }
            }
        }

        // Frontmatter + body
        $frontmatter = [
            'auto_generated' => 'true',
            'type'           => 'model',
            'class'          => $ref->getName(),
            'table'          => $table,
            'file'           => $relPath,
            'tags'           => '[model, auto]',
        ];
        $fmYaml = "---\n";
        foreach ($frontmatter as $k => $v) {
            $fmYaml .= "{$k}: {$v}\n";
        }
        $fmYaml .= "---\n\n";

        $body = "# {$name}\n\n";
        $body .= "> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.\n\n";
        $body .= "## Arquivo\n`{$relPath}`\n\n";
        $body .= "## Tabela\n`{$table}`\n\n";

        if ($traits) {
            $body .= "## Traits\n";
            foreach ($traits as $t) {
                $body .= "- `{$t}`\n";
            }
            $body .= "\n";
        }

        if ($fillable) {
            $body .= "## Fillable\n";
            foreach ($fillable as $f) {
                $body .= "- `{$f}`\n";
            }
            $body .= "\n";
        }

        if ($casts) {
            $body .= "## Casts\n| Coluna | Cast |\n|---|---|\n";
            foreach ($casts as $col => $cast) {
                $castStr = is_string($cast) ? $cast : json_encode($cast);
                $body .= "| `{$col}` | `{$castStr}` |\n";
            }
            $body .= "\n";
        }

        if ($relations) {
            $body .= "## Relações\n";
            foreach ($relations as $r) {
                $body .= "- `{$r['method']}()` — {$r['kind']}\n";
            }
            $body .= "\n";
        }

        $body .= "## Links sugeridos\n";
        $body .= "- Notas escritas à mão sobre esse model: procure no vault por `[[{$name}]]`\n";

        return $fmYaml . $body;
    }

    // ── Services ─────────────────────────────────────────────────────────────

    private function syncServices(): void
    {
        $servicesDir = $this->vaultPath . '/40 Services';
        if (! is_dir($servicesDir)) {
            mkdir($servicesDir, 0755, true);
        }

        $finder = (new Finder())
            ->files()
            ->in(app_path('Services'))
            ->name('*.php')
            ->depth('< 3');

        $count = 0;
        foreach ($finder as $file) {
            $relPath = str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file->getRealPath());
            $class   = $this->classFromPath($file->getRealPath(), 'App\\Services\\');

            if (! $class || ! class_exists($class)) {
                continue;
            }
            $ref = new ReflectionClass($class);
            if ($ref->isAbstract() || $ref->isInterface() || $ref->isTrait()) {
                continue;
            }

            $name = $ref->getShortName();
            $note = $this->renderServiceNote($ref, $relPath);
            $path = $servicesDir . '/' . $name . '.md';
            file_put_contents($path, $note);
            $count++;
        }
        $this->line("  {$count} services regenerados");
    }

    private function renderServiceNote(ReflectionClass $ref, string $relPath): string
    {
        $name = $ref->getShortName();

        $methods = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            if ($m->isConstructor() || $m->isDestructor()) {
                continue;
            }
            $params = array_map(
                fn ($p) => '$' . $p->getName(),
                $m->getParameters(),
            );
            $methods[] = [
                'name'   => $m->getName(),
                'params' => implode(', ', $params),
                'static' => $m->isStatic(),
            ];
        }

        $fm = "---\n";
        $fm .= "auto_generated: true\n";
        $fm .= "type: service\n";
        $fm .= "class: " . $ref->getName() . "\n";
        $fm .= "file: {$relPath}\n";
        $fm .= "tags: [service, auto]\n";
        $fm .= "---\n\n";

        $body  = "# {$name}\n\n";
        $body .= "> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.\n\n";
        $body .= "## Arquivo\n`{$relPath}`\n\n";

        if ($methods) {
            $body .= "## Métodos públicos\n| Método | Static | Assinatura |\n|---|---|---|\n";
            foreach ($methods as $m) {
                $static = $m['static'] ? '✅' : '';
                $body .= "| `{$m['name']}` | {$static} | `({$m['params']})` |\n";
            }
            $body .= "\n";
        }

        $body .= "## Links sugeridos\n";
        $body .= "- Notas escritas à mão sobre esse service: procure no vault por `[[{$name}]]`\n";

        return $fm . $body;
    }

    // ── Routes ───────────────────────────────────────────────────────────────

    private function syncRoutes(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->map(function ($r) {
                return [
                    'methods' => implode('|', $r->methods()),
                    'uri'     => $r->uri(),
                    'name'    => $r->getName() ?? '',
                    'action'  => $r->getActionName(),
                    'mw'      => implode(',', $r->middleware()),
                ];
            })
            ->reject(fn ($r) => str_contains($r['action'], 'Closure'))
            ->sortBy('uri')
            ->values();

        $opsDir = $this->vaultPath . '/60 Operations';
        if (! is_dir($opsDir)) {
            mkdir($opsDir, 0755, true);
        }

        $note  = "---\nauto_generated: true\ntype: routes\ntags: [routes, auto]\n---\n\n";
        $note .= "# Routes (auto-gerado)\n\n";
        $note .= "> `php artisan obsidian:sync --routes` regenera essa nota. Não edite à mão.\n\n";
        $note .= "Total: " . $routes->count() . " rotas\n\n";

        // Agrupar por prefixo (primeira parte da URI)
        $grouped = $routes->groupBy(function ($r) {
            $parts = explode('/', $r['uri']);
            return $parts[0] ?: 'root';
        });

        foreach ($grouped as $prefix => $rs) {
            $note .= "## /{$prefix}\n\n";
            $note .= "| Método | URI | Nome | Action |\n|---|---|---|---|\n";
            foreach ($rs as $r) {
                $action = str_replace('App\\Http\\Controllers\\', '', $r['action']);
                $note  .= "| `{$r['methods']}` | `/{$r['uri']}` | `{$r['name']}` | `{$action}` |\n";
            }
            $note .= "\n";
        }

        file_put_contents($opsDir . '/Routes.md', $note);
        $this->line("  {$routes->count()} rotas escritas em 60 Operations/Routes.md");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve FQCN a partir do path PHP. Assume PSR-4 padrão Laravel.
     */
    private function classFromPath(string $absPath, string $rootNamespace): ?string
    {
        $appPath = app_path();
        if (! str_starts_with($absPath, $appPath)) {
            return null;
        }
        $rel = substr($absPath, strlen($appPath) + 1);
        $rel = str_replace(['/', '\\'], '\\', $rel);
        $rel = preg_replace('/\.php$/', '', $rel);

        // Pra Models: $rel = 'Models\\Lead' → trim do prefix 'Models\\'
        $prefixWithoutApp = str_replace('App\\', '', $rootNamespace);
        $prefixWithoutApp = rtrim($prefixWithoutApp, '\\');

        if (! str_starts_with($rel, $prefixWithoutApp)) {
            return null;
        }

        return 'App\\' . $rel;
    }
}
