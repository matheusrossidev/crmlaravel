<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master\Traits;

trait ChecksMasterPermission
{
    protected function authorizeModule(string $module): void
    {
        if (! auth()->user()->canAccessModule($module)) {
            abort(403, 'Você não tem permissão para acessar este módulo.');
        }
    }
}
